<?php

namespace Tests\Feature;

use Database\Seeders\TicketCategoryMappingSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class TicketCategoryMappingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('ticket_types', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('ticket_categories', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('code')->unique(); $t->unsignedBigInteger('ticket_type_id')->nullable(); $t->timestamps();
        });
        Schema::create('ticket_sub_types', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('priority'); $t->unsignedBigInteger('ticket_category_id')->nullable(); $t->timestamps();
        });
        Schema::create('ticket_settings', function (Blueprint $t) { $t->id(); $t->integer('ticket_type_id'); $t->integer('ticket_sub_type_id'); });
        Schema::create('tickets', function (Blueprint $t) { $t->id(); $t->integer('tickettype_id'); $t->integer('ticketsubtype_id'); });
        DB::table('ticket_types')->insert([['id' => 16, 'name' => 'HRPSP POC'], ['id' => 17, 'name' => 'Jazzcash']]);
        $names = [
            'Laptop Issuance Status Required', 'Resignation Acceptance Letter Required', 'Experience Letter Required',
            'Final Settlement Calculation Issue', 'Commission Miscalculations', 'Salary Not Disbursed',
            'Pay slip Not Available', 'Pay slip Discrepancy', 'Salary Discrepancy', 'Fuel & CMA Not Paid',
            'Tax Over Deduction', 'Discrepancy in Pay for Leave or Absence', 'Commission Not Disbursed', 'Incentive Not Disbursed',
            'Office Environment and Facilities Issues', 'Lack of Transparency in Target Allocation', 'Favoritism in Target Setting',
            'Unsatisfactory Work Environment', 'Toxic Workplace Atmosphere', 'Dispute with Management',
        ];
        foreach ($names as $offset => $name) {
            $id = 36 + $offset; $type = $id < 50 ? 16 : 17;
            DB::table('ticket_sub_types')->insert(['id' => $id, 'name' => $name, 'priority' => $type === 16 ? 'Urgent' : 'Low']);
            DB::table('ticket_settings')->insert(['ticket_type_id' => $type, 'ticket_sub_type_id' => $id]);
        }
        DB::table('tickets')->insert(['id' => 101, 'tickettype_id' => 16, 'ticketsubtype_id' => 38]);
    }

    public function test_mapping_preserves_records_and_uses_previous_types_and_recommended_priorities(): void
    {
        $settings = DB::table('ticket_settings')->get()->toJson();
        $tickets = DB::table('tickets')->get()->toJson();
        $ids = DB::table('ticket_sub_types')->pluck('id')->all();
        $this->seed(TicketCategoryMappingSeeder::class);
        $this->assertSame(6, DB::table('ticket_categories')->count());
        $this->assertSame($ids, DB::table('ticket_sub_types')->pluck('id')->all());
        $this->assertSame($settings, DB::table('ticket_settings')->get()->toJson());
        $this->assertSame($tickets, DB::table('tickets')->get()->toJson());
        foreach (DB::table('ticket_sub_types')->get() as $sub) {
            $category = DB::table('ticket_categories')->find($sub->ticket_category_id);
            $this->assertSame($sub->id < 50 ? 16 : 17, $category->ticket_type_id);
        }
        $expected = [36 => 'Medium', 37 => 'Medium', 38 => 'Medium', 39 => 'High', 41 => 'Urgent', 42 => 'Medium', 44 => 'High', 51 => 'Medium', 52 => 'High', 54 => 'High', 55 => 'High'];
        foreach ($expected as $id => $priority) $this->assertSame($priority, DB::table('ticket_sub_types')->find($id)->priority);
        $this->assertSame('Urgent', DB::table('ticket_sub_types')->find(40)->priority);
        $this->assertSame('Low', DB::table('ticket_sub_types')->find(50)->priority);
        $this->assertSame('HRE', DB::table('ticket_categories')->find(DB::table('ticket_sub_types')->find(39)->ticket_category_id)->code);
    }

    public function test_rerunning_does_not_duplicate_or_change_existing_records(): void
    {
        $this->seed(TicketCategoryMappingSeeder::class);
        $before = DB::table('ticket_sub_types')->get()->toJson();
        $this->seed(TicketCategoryMappingSeeder::class);
        $this->assertSame(6, DB::table('ticket_categories')->count());
        $this->assertSame($before, DB::table('ticket_sub_types')->get()->toJson());
    }

    public function test_conflicting_historical_type_rolls_back_every_change(): void
    {
        DB::table('tickets')->insert(['tickettype_id' => 17, 'ticketsubtype_id' => 38]);
        try { $this->seed(TicketCategoryMappingSeeder::class); $this->fail('Expected conflict'); }
        catch (RuntimeException $e) { $this->assertStringContainsString('needs review', $e->getMessage()); }
        $this->assertSame(0, DB::table('ticket_categories')->count());
        $this->assertSame(0, DB::table('ticket_sub_types')->whereNotNull('ticket_category_id')->count());
        $this->assertSame('Urgent', DB::table('ticket_sub_types')->find(36)->priority);
    }

    public function test_unrelated_categories_and_subcategories_are_preserved(): void
    {
        DB::table('ticket_categories')->insert(['name' => 'Existing', 'code' => 'EXIST']);
        DB::table('ticket_sub_types')->insert(['id' => 99, 'name' => 'New issue for review', 'priority' => 'Low']);
        $this->seed(TicketCategoryMappingSeeder::class);
        $this->assertSame(7, DB::table('ticket_categories')->count());
        $this->assertNull(DB::table('ticket_sub_types')->find(99)->ticket_category_id);
        $this->assertSame('Low', DB::table('ticket_sub_types')->find(99)->priority);
    }
}
