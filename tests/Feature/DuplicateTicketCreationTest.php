<?php

namespace Tests\Feature;

use App\Exceptions\ActiveTicketAlreadyExists;
use App\Models\Ticket;
use App\Models\TicketTimeline;
use App\Models\User;
use App\Services\TicketCreationGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DuplicateTicketCreationTest extends TestCase
{
    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email');
            $table->string('password')->nullable(); $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id');
            $table->string('employee_id'); $table->string('report_to')->nullable();
        });
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('ticket_sub_types', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('priority')->nullable(); $table->timestamps();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tickettype_id');
            $table->unsignedBigInteger('ticketsubtype_id')->nullable();
            $table->unsignedBigInteger('requested_by'); $table->unsignedBigInteger('assigned_to');
            $table->string('status'); $table->text('description')->nullable();
            $table->string('ticket_attachment')->nullable(); $table->integer('current_level')->default(1);
            $table->timestamp('completed_at')->nullable(); $table->timestamp('escalation_time_start')->nullable();
            $table->timestamps();
        });
        Schema::create('ticket_timelines', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('ticket_id'); $table->unsignedBigInteger('assign_to');
            $table->timestamp('assign_time'); $table->string('assign_type'); $table->timestamps();
        });
        $this->requester = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);
        $manager = User::create(['name' => 'Manager', 'email' => 'manager@example.com']);
        DB::table('employees')->insert([
            ['user_id' => $this->requester->id, 'employee_id' => 'E1', 'report_to' => 'E2'],
            ['user_id' => $manager->id, 'employee_id' => 'E2', 'report_to' => null],
        ]);
        DB::table('ticket_types')->insert(['id' => 1, 'name' => 'JazzCash']);
        DB::table('ticket_sub_types')->insert([
            ['id' => 1, 'name' => 'Facilities'], ['id' => 2, 'name' => 'Payroll'],
        ]);
        $categories = require database_path('migrations/2026_11_25_000001_create_ticket_categories_table.php');
        $categories->up();
        $hierarchy = require database_path('migrations/2026_11_25_000002_add_ticket_hierarchy_relationships.php');
        $hierarchy->up();
        \App\Models\TicketCategory::create(['name' => 'General', 'code' => 'GEN', 'ticket_type_id' => 1]);
        DB::table('ticket_sub_types')->update(['ticket_category_id' => 1]);
        require_once database_path('migrations/2023_05_02_180827_create_permission_tables.php');
        (new \CreatePermissionTables())->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        \Spatie\Permission\Models\Permission::create(['name' => 'ticket.create', 'guard_name' => 'web']);
        $this->requester->givePermissionTo('ticket.create');
        Notification::fake();
    }

    private function input(array $overrides = []): array
    {
        return array_merge([
            'tickettype_id' => 1, 'ticketsubtype_id' => 1,
            'requested_by' => $this->requester->id, 'assigned_to' => 2,
            'description' => 'Test ticket', 'status' => 'Open',
        ], $overrides);
    }

    public function test_web_duplicate_redirects_to_existing_ticket_with_explanation(): void
    {
        $existing = Ticket::create($this->input());
        $this->actingAs($this->requester)
            ->post(route('tickets_store'), ['tickettype_id' => 1, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1])
            ->assertRedirect(route('ticket.view', ['id' => $existing->id, 'return_to' => route('tickets.index')]))
            ->assertSessionHas('duplicate_ticket_id', $existing->id)
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Please continue on the existing ticket'));
        $this->assertSame(1, Ticket::count());
        $this->assertSame(0, TicketTimeline::count());
        Notification::assertNothingSent();
    }

    public function test_api_duplicate_returns_json_and_existing_ticket_navigation_data(): void
    {
        $existing = Ticket::create($this->input(['status' => 'in_progress']));
        Sanctum::actingAs($this->requester);
        $this->postJson('/api/tickets', ['tickettype_id' => 1, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1])
            ->assertStatus(409)
            ->assertJsonPath('status', false)
            ->assertJsonPath('code', 'duplicate_active_ticket')
            ->assertJsonPath('existing_ticket_id', $existing->id)
            ->assertJsonPath('existing_ticket_status', 'in_progress')
            ->assertJsonPath('existing_ticket_url', url('/api/tickets/ticket/'.$existing->id));
        $this->assertSame(1, Ticket::count());
        Notification::assertNothingSent();
    }

    public function test_api_duplicate_is_json_even_without_accept_header(): void
    {
        $existing = Ticket::create($this->input());
        Sanctum::actingAs($this->requester);
        $this->post('/api/tickets', ['tickettype_id' => 1, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1])
            ->assertStatus(409)->assertJsonPath('existing_ticket_id', $existing->id);
    }

    public function test_active_status_spellings_are_blocked(): void
    {
        $guard = app(TicketCreationGuard::class);
        foreach (['Open', 'open', 'OPEN', 'in_progress', 'In Progress', 'inprogress', 'in-progress'] as $status) {
            Ticket::query()->delete();
            $existing = Ticket::create($this->input(['status' => $status]));
            try {
                $guard->assertCanCreate($this->requester->id, 1);
                $this->fail('Expected duplicate for '.$status);
            } catch (ActiveTicketAlreadyExists $exception) {
                $this->assertSame($existing->id, $exception->ticket->id);
            }
        }
    }

    public function test_closed_and_feedback_tickets_do_not_block_new_creation(): void
    {
        Ticket::create($this->input(['status' => 'Closed']));
        Ticket::create($this->input(['status' => 'Feedback']));
        $created = app(TicketCreationGuard::class)->create($this->input());
        $this->assertSame(3, Ticket::count());
        $this->assertSame($created->id, TicketTimeline::first()->ticket_id);
    }

    public function test_other_requesters_and_other_subtypes_do_not_block_creation(): void
    {
        Ticket::create($this->input(['requested_by' => 2]));
        Ticket::create($this->input(['ticketsubtype_id' => 2]));
        $created = app(TicketCreationGuard::class)->create($this->input());
        $this->assertSame($this->requester->id, $created->requested_by);
        $this->assertSame(3, Ticket::count());
    }

    public function test_missing_subtype_does_not_match_a_named_subtype(): void
    {
        Ticket::create($this->input(['ticketsubtype_id' => null]));
        $created = app(TicketCreationGuard::class)->create($this->input(['ticketsubtype_id' => null]));
        $this->assertNull($created->ticketsubtype_id);
        $this->assertSame(2, Ticket::count());
    }

    public function test_atomic_creation_rechecks_before_insert(): void
    {
        $guard = app(TicketCreationGuard::class);
        $guard->assertCanCreate($this->requester->id, 1);
        $existing = $guard->create($this->input());
        try {
            $guard->create($this->input());
            $this->fail('Second creation should fail');
        } catch (ActiveTicketAlreadyExists $exception) {
            $this->assertSame($existing->id, $exception->ticket->id);
        }
        $this->assertSame(1, Ticket::count());
        $this->assertSame(1, TicketTimeline::count());
    }

    public function test_existing_old_duplicates_redirect_to_the_oldest_active_ticket(): void
    {
        $oldest = Ticket::create($this->input(['status' => 'in_progress']));
        Ticket::create($this->input());
        try {
            app(TicketCreationGuard::class)->assertCanCreate($this->requester->id, 1);
            $this->fail('Expected duplicate');
        } catch (ActiveTicketAlreadyExists $exception) {
            $this->assertSame($oldest->id, $exception->ticket->id);
        }
    }

    public function test_new_web_ticket_still_creates_a_ticket_timeline_and_notification(): void
    {
        $this->actingAs($this->requester)
            ->post(route('tickets_store'), ['tickettype_id' => 1, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1, 'description' => 'Test ticket'])
            ->assertRedirect(route('tickets.index'));
        $this->assertSame(1, Ticket::count());
        $this->assertSame(1, TicketTimeline::count());
        Notification::assertCount(1);
    }

    public function test_new_api_ticket_returns_201_and_a_repeat_is_blocked(): void
    {
        Sanctum::actingAs($this->requester);
        $payload = ['tickettype_id' => 1, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1, 'description' => 'Test ticket'];
        $response = $this->postJson('/api/tickets', $payload)->assertCreated()->assertJsonPath('status', true);
        $this->postJson('/api/tickets', $payload)->assertStatus(409)
            ->assertJsonPath('existing_ticket_id', $response->json('data.id'));
        $this->assertSame(1, Ticket::count());
        $this->assertSame(1, TicketTimeline::count());
        Notification::assertCount(1);
    }

    public function test_ticket_and_timeline_are_rolled_back_together_on_failure(): void
    {
        Schema::drop('ticket_timelines');
        try {
            app(TicketCreationGuard::class)->create($this->input());
            $this->fail('Missing timeline table should fail');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertSame(0, Ticket::count());
        }
    }
}