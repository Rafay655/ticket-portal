<?php

namespace Tests\Feature;

use App\Exceptions\ActiveTicketAlreadyExists;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketSubType;
use App\Services\TicketHierarchy;
use Illuminate\Support\Facades\Validator;
use App\Models\TicketTimeline;
use App\Models\User;
use App\Services\TicketCreationGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketHierarchyTest extends TestCase
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
        DB::table('ticket_types')->insert(['id' => 2, 'name' => 'Other Type']);
        TicketCategory::create(['name' => 'Other Category', 'code' => 'OTHER', 'ticket_type_id' => 2]);
        TicketCategory::create(['name' => 'Second Category', 'code' => 'SECOND', 'ticket_type_id' => 1]);
        TicketSubType::where('id', 1)->update(['priority' => 'Urgent']);
        TicketSubType::where('id', 2)->update(['priority' => 'High']);
        TicketSubType::create(['name' => 'Other Sub-Category', 'priority' => 'Low', 'ticket_category_id' => 2]);
        TicketSubType::create(['name' => 'Legacy Unmapped', 'priority' => 'Medium']);
        Schema::create('abouts', function (Blueprint $table) { $table->id(); $table->string('name'); });
        DB::table('abouts')->insert(['name' => 'Flexi Desk']);
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('type'); $table->morphs('notifiable'); $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        Schema::create('priorities', function (Blueprint $table) { $table->id(); $table->string('priority'); });
        foreach (['Urgent', 'High', 'Medium', 'Low'] as $priority) DB::table('priorities')->insert(['priority' => $priority]);
        Schema::create('ticket_settings', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('ticket_type_id'); $table->unsignedBigInteger('ticket_sub_type_id'); $table->timestamps();
        });
        Schema::create('ticket_escalations', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('ticket_setting_id'); $table->integer('days'); $table->integer('level'); $table->timestamps();
        });
    }

    private function input(array $overrides = []): array
    {
        return array_merge([
            'tickettype_id' => 1, 'ticketsubtype_id' => 1,
            'requested_by' => $this->requester->id, 'assigned_to' => 2,
            'description' => 'Test ticket', 'status' => 'Open',
        ], $overrides);
    }


    private function allow(string $permission): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $this->requester->givePermissionTo($permission);
    }

    public function test_mobile_create_stores_the_complete_hierarchy(): void
    {
        Sanctum::actingAs($this->requester);
        $this->postJson('/api/tickets', ['tickettype_id' => 1, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1])
            ->assertCreated()->assertJsonPath('data.ticket_category_id', 1)->assertJsonPath('data.ticket_category_name', 'General');
        $this->assertSame(1, Ticket::first()->ticketsubtype_id);
        $this->assertSame(1, Ticket::first()->ticket_category_id);
    }

    public function test_old_mobile_client_can_omit_category_for_a_mapped_sub_category(): void
    {
        Sanctum::actingAs($this->requester);
        $this->postJson('/api/tickets', ['tickettype_id' => 1, 'ticketsubtype_id' => 1])->assertCreated()->assertJsonPath('data.ticket_category_id', 1);
    }

    public function test_cross_type_sub_category_is_rejected_before_any_creation(): void
    {
        Sanctum::actingAs($this->requester);
        $this->postJson('/api/tickets', ['tickettype_id' => 2, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('ticketsubtype_id');
        $this->assertSame(0, Ticket::count());
        $this->assertSame(0, TicketTimeline::count());
        Notification::assertNothingSent();
    }

    public function test_wrong_middle_category_is_rejected_even_under_the_same_type(): void
    {
        Sanctum::actingAs($this->requester);
        $this->postJson('/api/tickets', ['tickettype_id' => 1, 'ticket_category_id' => 3, 'ticketsubtype_id' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('ticket_category_id');
    }

    public function test_unmapped_legacy_sub_category_returns_clear_json_even_without_accept_header(): void
    {
        Sanctum::actingAs($this->requester);
        $this->post('/api/tickets', ['tickettype_id' => 1, 'ticketsubtype_id' => 4])
            ->assertUnprocessable()->assertJsonPath('status', false)->assertJsonValidationErrors('ticketsubtype_id');
        $this->assertSame(0, Ticket::count());
    }

    public function test_web_form_requires_category_and_rejects_tampered_parent_selection(): void
    {
        $this->actingAs($this->requester)->post(route('tickets_store'), ['tickettype_id' => 1, 'ticketsubtype_id' => 1])
            ->assertSessionHasErrors('ticket_category_id');
        $this->post(route('tickets_store'), ['tickettype_id' => 1, 'ticket_category_id' => 3, 'ticketsubtype_id' => 1])
            ->assertSessionHasErrors('ticket_category_id');
        $this->assertSame(0, Ticket::count());
    }

    public function test_web_create_saves_the_category_snapshot(): void
    {
        $this->actingAs($this->requester)->post(route('tickets_store'), ['tickettype_id' => 1, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1])
            ->assertRedirect(route('tickets.index'));
        $this->assertSame(1, Ticket::first()->ticket_category_id);
    }

    public function test_duplicate_restriction_still_uses_final_sub_category(): void
    {
        Sanctum::actingAs($this->requester);
        $data = ['tickettype_id' => 1, 'ticket_category_id' => 1, 'ticketsubtype_id' => 1];
        $first = $this->postJson('/api/tickets', $data)->assertCreated();
        $this->postJson('/api/tickets', $data)->assertStatus(409)->assertJsonPath('existing_ticket_id', $first->json('data.id'));
        $this->assertSame(1, Ticket::count());
    }

    public function test_lookup_endpoints_filter_each_level_and_preserve_old_id_name_fields(): void
    {
        Sanctum::actingAs($this->requester);
        $this->getJson('/api/ticket-types/1/categories')->assertOk()->assertJsonCount(2)->assertJsonFragment(['id' => 1, 'code' => 'GEN']);
        $this->getJson('/api/ticket-categories/1/sub-categories')->assertOk()->assertJsonCount(2)->assertJsonFragment(['id' => 1, 'name' => 'Facilities']);
        $this->getJson('/api/ticket-types/1/sub-types')->assertOk()->assertJsonCount(2)->assertJsonFragment(['id' => 2, 'name' => 'Payroll']);
        $this->getJson('/api/ticket-types/2/sub-types')->assertOk()->assertJsonCount(1)->assertJsonFragment(['id' => 3]);
        TicketCategory::create(['name' => 'Unmapped', 'code' => 'UNMAPPED']);
        $this->getJson('/api/ticket-categories/4/sub-categories')->assertNotFound();
    }

    public function test_existing_shared_sub_category_history_is_not_silently_reclassified(): void
    {
        $old = Ticket::create($this->input(['tickettype_id' => 2]));
        $this->assertNull($old->ticket_category_id);
        $this->assertNull($old->ticket_category_name);
        $this->assertSame(1, $old->ticketsubtype_id);
    }

    public function test_admin_can_map_a_legacy_sub_category_without_replacing_its_id_or_priority(): void
    {
        $this->allow('ticketSubType.edit');
        $old = Ticket::create($this->input(['ticketsubtype_id' => 4]));
        $this->actingAs($this->requester)->put(route('ticketsubtypes.update', 4), ['name' => 'Legacy Unmapped', 'priority' => 'Medium', 'ticket_type_id' => 1, 'ticket_category_id' => 3])
            ->assertRedirect(route('ticketsubtypes.index'));
        $this->assertSame(3, TicketSubType::find(4)->ticket_category_id);
        $this->assertSame('Medium', TicketSubType::find(4)->priority);
        $this->assertSame(4, $old->fresh()->ticketsubtype_id);
        $this->assertNull($old->fresh()->ticket_category_id);
        $this->assertSame('Second Category', $old->fresh()->ticket_category_name);
    }

    public function test_in_use_category_and_sub_category_cannot_be_reparented(): void
    {
        $this->allow('ticketCategory.edit');
        $this->allow('ticketSubType.edit');
        Ticket::create($this->input(['ticket_category_id' => 1]));
        $this->actingAs($this->requester)->putJson(route('ticketcategories.update', 1), ['name' => 'General', 'code' => 'GEN', 'ticket_type_id' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('ticket_type_id');
        $this->putJson(route('ticketsubtypes.update', 1), ['name' => 'Facilities', 'priority' => 'Urgent', 'ticket_type_id' => 1, 'ticket_category_id' => 3])
            ->assertUnprocessable()->assertJsonValidationErrors('ticket_category_id');
    }

    public function test_parent_delete_guards_preserve_child_and_ticket_records(): void
    {
        $this->allow('ticketCategory.delete');
        $this->allow('ticketSubType.delete');
        $this->allow('ticketType.delete');
        $ticket = Ticket::create($this->input(['ticket_category_id' => 1]));
        $this->actingAs($this->requester)->deleteJson(route('ticketcategories.destroy', 1))->assertStatus(409);
        $this->deleteJson(route('ticketsubtypes.destroy', 1))->assertOk()->assertJsonPath('0', 'error');
        $this->deleteJson(route('tickettypes.destroy', 1))->assertOk()->assertJsonPath('0', 'error');
        $this->assertNotNull($ticket->fresh());
        $this->assertNotNull(TicketCategory::find(1));
    }

    public function test_sub_category_validation_requires_matching_parents_and_priority(): void
    {
        $this->allow('ticketSubType.create');
        $this->actingAs($this->requester)->postJson(route('ticketsubtypes.store'), ['name' => 'Invalid', 'priority' => 'Urgent', 'ticket_type_id' => 1, 'ticket_category_id' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('ticket_category_id');
        $this->post(route('ticketsubtypes.store'), ['name' => 'New Leaf', 'priority' => 'High', 'ticket_type_id' => 1, 'ticket_category_id' => 3])
            ->assertRedirect(route('ticketsubtypes.index'));
        $this->assertSame(3, TicketSubType::where('name', 'New Leaf')->first()->ticket_category_id);
    }

    public function test_admin_mapping_and_ticket_creation_screens_render_the_new_hierarchy(): void
    {
        $this->allow('ticketSubType.create'); $this->allow('ticketSubType.index'); $this->allow('ticketSubType.edit');
        $this->allow('ticketCategory.create'); $this->allow('ticketSettings.create');
        $this->actingAs($this->requester)->get(route('tickets.create'))->assertOk()->assertSee('Ticket Category')->assertSee('Ticket Sub-Category')->assertSee('data-ticket-hierarchy', false);
        $this->get(route('ticketsubtypes.create'))->assertOk()->assertSee('Ticket Category')->assertSee('Ticket Sub-Category Name');
        $this->get(route('ticketsubtypes.index'))->assertOk()->assertSee('Not assigned');
        $this->get(route('ticketsubtypes.edit', 1))->assertOk()->assertSee('Facilities')->assertSee('data-ticket-hierarchy', false);
        $this->get(route('ticketcategories.create'))->assertOk()->assertSee('Ticket Type');
        $this->get(route('ticketsetting.create'))->assertOk()->assertSee('Ticket Category');
    }

    public function test_ticket_settings_cannot_define_a_conflicting_tree(): void
    {
        $this->allow('ticketSettings.create');
        $this->actingAs($this->requester)->postJson(route('ticketsetting.store'), ['ticket_type_id' => 1, 'ticket_category_id' => 2, 'ticket_sub_type_id' => 1])->assertUnprocessable();
        $this->post(route('ticketsetting.store'), ['ticket_type_id' => 1, 'ticket_category_id' => 1, 'ticket_sub_type_id' => 1, 'escalation' => []])->assertRedirect(route('ticketsetting.index'));
        $this->assertSame(1, DB::table('ticket_settings')->count());
    }

    public function test_api_update_keeps_hierarchy_consistent_and_validates_mismatches(): void
    {
        $ticket = Ticket::create($this->input(['ticket_category_id' => 1]));
        Sanctum::actingAs($this->requester);
        $this->postJson('/api/tickets/'.$ticket->id.'/update', ['tickettype_id' => 1])->assertOk();
        $this->assertSame(1, $ticket->fresh()->ticket_category_id);
        $this->postJson('/api/tickets/'.$ticket->id.'/update', ['tickettype_id' => 2])->assertUnprocessable()->assertJsonValidationErrors('ticketsubtype_id');
    }

    public function test_bulk_import_accepts_old_column_names_only_for_an_unambiguous_mapped_child(): void
    {
        $row = ['ticket_type' => 'JazzCash', 'ticket_sub_type' => 'Facilities', 'requested_by' => 1, 'assigned_to' => 2, 'status' => 'Open'];
        $import = new \App\Imports\TicketImport();
        $this->assertFalse(Validator::make([$row], $import->rules())->fails());
        $ticket = $import->model($row);
        $this->assertSame(1, $ticket->ticket_category_id);
        TicketSubType::create(['name' => 'Facilities', 'priority' => 'Low', 'ticket_category_id' => 3]);
        $this->assertNull($import->model($row));
        $row['ticket_category'] = 'GEN';
        $this->assertSame(1, $import->model($row)->ticket_category_id);
        unset($row['ticket_sub_type']); $row['ticket_sub_category'] = 'Facilities';
        $this->assertFalse(Validator::make([$row], $import->rules())->fails());
    }

    public function test_exports_include_the_middle_level_and_keep_ticket_reference(): void
    {
        $ticket = Ticket::create($this->input(['ticket_category_id' => 1]));
        $rows = (new \App\Exports\FirstTabExport(collect([$ticket])))->array();
        $this->assertContains('Ticket Category', $rows[0]);
        $this->assertContains('Ticket Sub-Category', $rows[0]);
        $this->assertSame($ticket->id, $rows[1][0]);
        $this->assertContains('General', $rows[1]);
    }

    public function test_migration_preserves_old_records_and_leaves_links_unassigned(): void
    {
        config(['database.connections.legacy_hierarchy' => array_merge(config('database.connections.sqlite'), ['database' => ':memory:']), 'database.default' => 'legacy_hierarchy']);
        DB::purge('legacy_hierarchy');
        Schema::create('ticket_types', fn (Blueprint $table) => [$table->id(), $table->string('name')]);
        Schema::create('ticket_sub_types', fn (Blueprint $table) => [$table->id(), $table->string('name'), $table->string('priority')]);
        Schema::create('tickets', fn (Blueprint $table) => [$table->id(), $table->unsignedBigInteger('tickettype_id'), $table->unsignedBigInteger('ticketsubtype_id')]);
        $categories = require database_path('migrations/2026_11_25_000001_create_ticket_categories_table.php'); $categories->up();
        DB::table('ticket_types')->insert(['id' => 71, 'name' => 'Legacy Type']);
        DB::table('ticket_categories')->insert(['id' => 80, 'name' => 'Legacy Category', 'code' => 'LEGACY']);
        DB::table('ticket_sub_types')->insert(['id' => 90, 'name' => 'Legacy Subtype', 'priority' => 'High']);
        DB::table('tickets')->insert(['id' => 100, 'tickettype_id' => 71, 'ticketsubtype_id' => 90]);
        $hierarchy = require database_path('migrations/2026_11_25_000002_add_ticket_hierarchy_relationships.php'); $hierarchy->up();
        $this->assertNull(DB::table('ticket_categories')->where('id', 80)->value('ticket_type_id'));
        $this->assertNull(DB::table('ticket_sub_types')->where('id', 90)->value('ticket_category_id'));
        $this->assertNull(DB::table('tickets')->where('id', 100)->value('ticket_category_id'));
        $this->assertSame(90, DB::table('tickets')->where('id', 100)->value('ticketsubtype_id'));
        $this->assertSame('High', DB::table('ticket_sub_types')->where('id', 90)->value('priority'));
    }

    public function test_ticket_search_matches_details_and_hierarchy_without_leaking_other_requesters(): void
    {
        $visible = Ticket::create($this->input(['description' => 'Office printer problem', 'ticket_category_id' => 1]));
        Ticket::create($this->input(['requested_by' => 2, 'description' => 'Office printer problem', 'ticket_category_id' => 1]));
        foreach (['printer', 'General', 'GEN', 'Facilities', 'Urgent', 'JazzCash', 'Open'] as $term) {
            $request = \Illuminate\Http\Request::create('/', 'GET', ['search' => $term]);
            $query = Ticket::where('requested_by', $this->requester->id);
            \App\Support\ListingFilters::tickets($query, $request);
            $this->assertSame([$visible->id], $query->pluck('id')->all(), $term);
        }
    }

    public function test_category_donut_counts_snapshots_and_legacy_mapping_within_scope(): void
    {
        Ticket::create($this->input(['ticket_category_id' => 1]));
        Ticket::create($this->input());
        Ticket::create($this->input(['requested_by' => 2]));
        $rows = app(\App\Services\DashboardCategoryChart::class)->counts(Ticket::where('requested_by', $this->requester->id));
        $this->assertCount(1, $rows);
        $this->assertSame('General', $rows->first()->name);
        $this->assertSame(2, $rows->first()->counter);
        $this->assertEquals(100, $rows->first()->percentage);
    }

    public function test_category_donut_handles_unmapped_and_empty_tickets(): void
    {
        $chart = app(\App\Services\DashboardCategoryChart::class);
        $this->assertCount(0, $chart->counts(Ticket::query()));
        Ticket::create($this->input(['tickettype_id' => 2]));
        $rows = $chart->counts(Ticket::query());
        $this->assertSame('Not assigned', $rows->first()->name);
        $this->assertSame(1, $rows->first()->counter);
    }

    public function test_audio_notification_excludes_raw_recording_and_bounds_database_payload(): void
    {
        $ticket = Ticket::create($this->input());
        $notification = new \App\Notifications\CommentAddedNotification([
            'id' => $this->requester->id, 'ticket_id' => $ticket->id,
            'comment' => ['comment' => str_repeat('Comment ', 20000), 'voice_note' => 'data:audio/webm;base64,'.str_repeat('A', 200000)],
        ]);
        $data = $notification->toArray($this->requester);
        $this->assertLessThan(12000, strlen(json_encode($data)));
        $decoded = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($data['encrypted_data']), true);
        $this->assertStringNotContainsString('data:audio', json_encode($decoded));
        $this->assertStringNotContainsString('data:audio', json_encode($notification->userData));
        $comment = json_decode($decoded['comment'], true);
        $this->assertTrue($comment['has_voice_note']);
    }

    public function test_web_and_mobile_reject_empty_comments(): void
    {
        $ticket = Ticket::create($this->input());
        $this->actingAs($this->requester)->post(route('comment.add'), ['ticket_id' => $ticket->id, 'description' => '   '])->assertSessionHasErrors('description');
        Sanctum::actingAs($this->requester);
        $this->postJson('/api/tickets/'.$ticket->id.'/reply', ['description' => '   '])->assertUnprocessable()->assertJsonValidationErrors('description');
        $this->assertSame('Open', $ticket->fresh()->status);
    }

    public function test_comment_content_rejects_unfinished_or_invalid_audio_and_accepts_voice_only(): void
    {
        foreach ([['description' => 'Text', 'is_recording' => 1], ['voice_note' => 'data:audio/webm;base64,@@@']] as $input) {
            try {
                \App\Support\CommentContent::validate(\Illuminate\Http\Request::create('/', 'POST', $input));
                $this->fail('Expected validation failure');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertArrayHasKey('voice_note', $exception->errors());
            }
        }
        $audio = 'data:audio/webm;codecs=opus;base64,'.base64_encode('test audio bytes');
        \App\Support\CommentContent::validate(\Illuminate\Http\Request::create('/', 'POST', ['voice_note' => $audio]));
        $this->assertSame(['test audio bytes', 'webm'], \App\Support\CommentContent::audio($audio));
    }

    public function test_get_close_url_redirects_without_closing_ticket(): void
    {
        $ticket = Ticket::create($this->input());
        $this->actingAs($this->requester)->get('/ticket/close/'.$ticket->id)
            ->assertRedirect(route('ticket.view', ['id' => $ticket->id]));
        $this->assertSame('Open', $ticket->fresh()->status);
    }

    public function test_close_requires_nonempty_resolution_and_assignee(): void
    {
        $ticket = Ticket::create($this->input());
        $this->actingAs($this->requester)->post('/ticket/close/'.$ticket->id, ['description' => 'Done'])->assertForbidden();
        $assignee = User::find(2);
        $this->actingAs($assignee)->post('/ticket/close/'.$ticket->id, ['description' => '   '])->assertSessionHasErrors('description');
        $this->assertSame('Open', $ticket->fresh()->status);
    }
}
