<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketCategoryMappingSeeder extends Seeder
{
    private const CATEGORIES = [
        'ITA' => ['IT & Asset Management', 'HRPSP POC'],
        'HRE' => ['HR & Employee Separation', 'HRPSP POC'],
        'PAY' => ['Payroll & Compensation', 'HRPSP POC'],
        'COM' => ['Commission & Incentives', 'HRPSP POC'],
        'STM' => ['Sales & Target Management', 'Jazzcash'],
        'WER' => ['Workplace & Employee Relations', 'Jazzcash'],
    ];

    // Only explicit recommendations change priority. Other priorities are preserved.
    private const SUBCATEGORIES = [
        'Laptop Issuance Status Required' => ['ITA', 'Medium'],
        'Resignation Acceptance Letter Required' => ['HRE', 'Medium'],
        'Experience Letter Required' => ['HRE', 'Medium'],
        'Final Settlement Calculation Issue' => ['HRE', 'High'],
        'Commission Miscalculations' => ['COM', null],
        'Salary Not Disbursed' => ['PAY', 'Urgent'],
        'Payslip Not Available' => ['PAY', 'Medium'],
        'Payslip Discrepancy' => ['PAY', null],
        'Salary Discrepancy' => ['PAY', 'High'],
        'Fuel & CMA Not Paid' => ['PAY', null],
        'Tax Over Deduction' => ['PAY', null],
        'Discrepancy in Pay for Leave or Absence' => ['PAY', null],
        'Commission Not Disbursed' => ['COM', null],
        'Incentive Not Disbursed' => ['COM', null],
        'Office Environment and Facilities Issues' => ['WER', null],
        'Lack of Transparency in Target Allocation' => ['STM', 'Medium'],
        'Favoritism in Target Setting' => ['STM', 'High'],
        'Unsatisfactory Work Environment' => ['WER', null],
        'Toxic Workplace Atmosphere' => ['WER', 'High'],
        'Dispute with Management' => ['WER', 'High'],
    ];

    private function normalize(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($name));
    }

    public function run(): void
    {
        DB::transaction(function () {
            $types = DB::table('ticket_types')->get()->groupBy(fn ($row) => $this->normalize($row->name));
            $subcategories = DB::table('ticket_sub_types')->lockForUpdate()->get()->groupBy(fn ($row) => $this->normalize($row->name));
            $now = now();
            $mapped = 0;
            foreach (self::CATEGORIES as $code => [$name, $typeName]) {
                $matches = $types->get($this->normalize($typeName), collect());
                if ($matches->count() !== 1) throw new RuntimeException('Missing or ambiguous Ticket Type: '.$typeName);
                $typeId = $matches->first()->id;
                $category = DB::table('ticket_categories')->where('code', $code)->lockForUpdate()->first();
                if ($category && ($category->name !== $name || ($category->ticket_type_id !== null && (int) $category->ticket_type_id !== (int) $typeId))) {
                    throw new RuntimeException('Category code conflict: '.$code);
                }
                if (!$category) {
                    $categoryId = DB::table('ticket_categories')->insertGetId([
                        'name' => $name, 'code' => $code, 'ticket_type_id' => $typeId, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                } else {
                    $categoryId = $category->id;
                    if ($category->ticket_type_id === null) DB::table('ticket_categories')->where('id', $categoryId)->update(['ticket_type_id' => $typeId, 'updated_at' => $now]);
                }
                foreach (self::SUBCATEGORIES as $subName => [$parent, $priority]) {
                    if ($parent !== $code) continue;
                    $rows = $subcategories->get($this->normalize($subName), collect());
                    if ($rows->isEmpty()) throw new RuntimeException('Expected Sub-Category not found: '.$subName);
                    foreach ($rows as $sub) {
                        $legacyTypes = DB::table('ticket_settings')->where('ticket_sub_type_id', $sub->id)->pluck('ticket_type_id');
                        $usedTypes = DB::table('tickets')->where('ticketsubtype_id', $sub->id)->pluck('tickettype_id');
                        $previousTypes = $legacyTypes->merge($usedTypes)->unique()->values();
                        if ($previousTypes->count() !== 1 || (int) $previousTypes->first() !== (int) $typeId) {
                            throw new RuntimeException('Previous Ticket Type mapping needs review: '.$sub->name);
                        }
                        if ($sub->ticket_category_id !== null && (int) $sub->ticket_category_id !== (int) $categoryId) {
                            throw new RuntimeException('Already mapped to another category: '.$sub->name);
                        }
                        $changes = [];
                        if ($sub->ticket_category_id === null) $changes['ticket_category_id'] = $categoryId;
                        if ($priority !== null && $priority !== $sub->priority) $changes['priority'] = $priority;
                        if ($changes) DB::table('ticket_sub_types')->where('id', $sub->id)->update($changes + ['updated_at' => $now]);
                        $mapped++;
                    }
                }
            }
            $this->command?->info('Mapped '.$mapped.' existing Sub-Categories into 6 Ticket Categories. Ticket IDs and legacy settings preserved.');
        });
    }
}
