<?php

namespace App\Imports;

use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\TicketSubType;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;

class TicketImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use RemembersRowNumber;

    public $failures = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $ticketType = TicketType::where('name', $row['ticket_type'] ?? '')->first();
        $subName = $row['ticket_sub_category'] ?? $row['ticket_sub_type'] ?? '';
        $categoryLabel = $row['ticket_category'] ?? null;
        $matches = TicketSubType::with('category')->where('name', $subName)
            ->whereHas('category', function ($query) use ($ticketType, $categoryLabel) {
                $query->where('ticket_type_id', $ticketType?->id);
                if ($categoryLabel) {
                    $query->where(fn ($query) => $query->where('name', $categoryLabel)->orWhere('code', strtoupper($categoryLabel)));
                }
            })->get();
        if (!$ticketType || $matches->count() !== 1) {
            $this->onFailure(new Failure($this->getRowNumber() ?? 1, 'ticket_sub_category', ['Choose a mapped Ticket Type / Ticket Category / Ticket Sub-Category combination. Include ticket_category when the sub-category name is ambiguous.'], $row));
            return null;
        }
        $ticketSubType = $matches->first();
        $ticketTypeId = $ticketType->id;
        $ticketSubTypeId = $ticketSubType->id;

        // Creating a new ticket model instance
        return new Ticket([
            'tickettype_id' => $ticketTypeId,
            'ticket_category_id' => $ticketSubType->ticket_category_id,
            'ticketsubtype_id' => $ticketSubTypeId,
            'requested_by' => $row['requested_by'],
            'assigned_to' => $row['assigned_to'],
            'status' => $row['status'],
            'created_at' => now(),
            'updated_at' => now(),
            'escalation_time_start' => now(),
            'current_level' => 1
        ]);
    }

    /**
     * Define validation rules for the imported data.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            '*.ticket_type' => 'required|exists:ticket_types,name',
            '*.ticket_sub_type' => 'required_without:*.ticket_sub_category|nullable|exists:ticket_sub_types,name',
            '*.ticket_sub_category' => 'required_without:*.ticket_sub_type|nullable|exists:ticket_sub_types,name',
            '*.ticket_category' => 'nullable|string',
            '*.requested_by' => 'required|exists:users,id',
            '*.assigned_to' => 'required|exists:users,id',
            // '*.assigned_to_user_id' => 'nullable|exists:users,id',
            '*.status' => 'required|string',
           
        ];
    }

    /**
     * Collect the failures and store them for returning to the front-end.
     *
     * @param Failure ...$failures
     */
    public function onFailure(Failure ...$failures)
    {
        // Collect all failures
        $this->failures = $failures;

        // Optionally, you can log failures for debugging purposes
        Log::error('Import failures:', ['failures' => $this->failures]);

        // You can return failures back to the session to display in the view
        session()->flash('failures', $this->failures);
    }
}
