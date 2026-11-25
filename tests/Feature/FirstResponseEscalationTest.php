<?php

namespace Tests\Feature;

use App\Models\BusinessHours;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketSubType;
use App\Models\TicketTimeline;
use App\Models\User;
use App\Services\BusinessHoursClock;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FirstResponseEscalationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'ticket_escalation.business_hours_id' => 2]);
        DB::purge('sqlite');
        Carbon::setTestNow(Carbon::parse('2026-11-25 12:00:00', 'Asia/Karachi'));
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password')->nullable(); $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('employee_id'); $table->string('report_to')->nullable();
        });
        Schema::create('ticket_sub_types', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('priority')->nullable(); $table->timestamps();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tickettype_id')->default(1); $table->unsignedBigInteger('ticketsubtype_id')->nullable();
            $table->unsignedBigInteger('requested_by'); $table->unsignedBigInteger('assigned_to'); $table->unsignedBigInteger('prev_assigned')->nullable();
            $table->string('status'); $table->integer('current_level')->default(1); $table->timestamp('escalation_time_start')->nullable(); $table->timestamps();
        });
        Schema::create('ticket_timelines', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('ticket_id'); $table->unsignedBigInteger('assign_to');
            $table->timestamp('assign_time'); $table->string('assign_type'); $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('ticket_id'); $table->unsignedBigInteger('commenter'); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->text('description')->nullable();
            foreach (['urgent', 'high', 'medium', 'low'] as $priority) {
                $table->string($priority.'_first_response_time')->nullable();
                $table->integer($priority.'_resolution_time')->default(10000);
                $table->string($priority.'_operational_hours')->default('business_hours');
                $table->string($priority.'_escalation')->default('on');
            }
            $table->timestamps();
        });
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('timezone'); $table->string('businesshourstype');
            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                $table->string($day)->default('off'); $table->string($day.'_start')->nullable(); $table->string($day.'_end')->nullable();
            }
            $table->timestamps();
        });
        foreach ([1 => 'Requester', 2 => 'Assignee', 3 => 'Manager', 4 => 'Senior Manager'] as $id => $name) {
            User::create(['name' => $name, 'email' => 'user'.$id.'@example.com']);
        }
        DB::table('employees')->insert([
            ['user_id' => 2, 'employee_id' => 'E2', 'report_to' => 'E3'],
            ['user_id' => 3, 'employee_id' => 'E3', 'report_to' => 'E4'],
            ['user_id' => 4, 'employee_id' => 'E4', 'report_to' => null],
        ]);
        TicketSubType::create(['name' => 'Facilities', 'priority' => 'Urgent']);
        SlaPolicy::create(['name' => 'Test SLA', 'urgent_first_response_time' => 60, 'high_first_response_time' => 90, 'medium_first_response_time' => 120, 'low_first_response_time' => 180]);
        $calendar = ['id' => 2, 'name' => 'Business hours', 'timezone' => 'Asia/Karachi (UTC +05:00)', 'businesshourstype' => 'custom'];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            $calendar[$day] = 'on'; $calendar[$day.'_start'] = '09:00'; $calendar[$day.'_end'] = '17:00';
        }
        DB::table('business_hours')->insert($calendar);
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function ticket(array $data = []): Ticket
    {
        return Ticket::create(array_merge(['tickettype_id' => 1, 'ticketsubtype_id' => 1, 'requested_by' => 1, 'assigned_to' => 2, 'status' => 'Open', 'current_level' => 1, 'escalation_time_start' => '2026-11-25 11:00:00'], $data));
    }

    public function test_open_ticket_escalates_at_first_response_threshold_not_resolution_time(): void
    {
        $ticket = $this->ticket();
        $this->assertTrue($ticket->shouldEscalate());
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $ticket->refresh();
        $this->assertSame(3, $ticket->assigned_to);
        $this->assertSame(2, $ticket->prev_assigned);
        $this->assertSame(2, $ticket->current_level);
        $this->assertSame('2026-11-25 12:00:00', $ticket->escalation_time_start);
        $this->assertSame('By Auto Escalation', TicketTimeline::first()->assign_type);
        Notification::assertCount(1);
    }

    public function test_only_open_tickets_are_escalated(): void
    {
        foreach (['in_progress', 'In Progress', 'Closed', 'Feedback'] as $status) $this->ticket(['status' => $status]);
        $open = $this->ticket(['status' => 'open']);
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->assertSame(1, TicketTimeline::count());
        $this->assertSame(3, $open->fresh()->assigned_to);
        $this->assertSame(4, Ticket::where('assigned_to', 2)->count());
    }

    public function test_ticket_before_threshold_is_not_escalated_even_if_legacy_resolution_time_is_short(): void
    {
        SlaPolicy::first()->update(['urgent_resolution_time' => 1]);
        $ticket = $this->ticket(['escalation_time_start' => '2026-11-25 11:01:00']);
        $this->assertFalse($ticket->shouldEscalate());
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->assertSame(2, $ticket->fresh()->assigned_to);
        Notification::assertNothingSent();
    }

    public function test_each_priority_uses_its_own_first_response_target(): void
    {
        foreach (['Urgent' => 60, 'High' => 90, 'Medium' => 120, 'Low' => 180] as $priority => $minutes) {
            TicketSubType::first()->update(['priority' => $priority]);
            $ticket = $this->ticket(['escalation_time_start' => Carbon::now()->subMinutes($minutes)->toDateTimeString()]);
            $this->assertTrue($ticket->shouldEscalate());
            $ticket->escalation_time_start = Carbon::now()->subMinutes($minutes - 1)->toDateTimeString();
            $this->assertFalse($ticket->shouldEscalate());
        }
    }

    public function test_escalation_switch_off_prevents_escalation(): void
    {
        SlaPolicy::first()->update(['urgent_escalation' => 'off']);
        $ticket = $this->ticket();
        $this->assertFalse($ticket->shouldEscalate());
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->assertSame(2, $ticket->fresh()->assigned_to);
    }

    public function test_assignee_response_stops_first_response_escalation_even_if_status_was_left_open(): void
    {
        $ticket = $this->ticket();
        DB::table('comments')->insert(['ticket_id' => $ticket->id, 'commenter' => 2, 'created_at' => '2026-11-25 11:30:00']);
        $this->assertFalse($ticket->shouldEscalate());
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->assertSame(2, $ticket->fresh()->assigned_to);
    }

    public function test_requester_comments_and_old_assignee_comments_do_not_satisfy_current_first_response(): void
    {
        $ticket = $this->ticket();
        DB::table('comments')->insert([
            ['ticket_id' => $ticket->id, 'commenter' => 1, 'created_at' => '2026-11-25 11:30:00'],
            ['ticket_id' => $ticket->id, 'commenter' => 2, 'created_at' => '2026-11-25 10:30:00'],
        ]);
        $this->assertTrue($ticket->shouldEscalate());
    }

    public function test_nonworking_days_and_afterhours_do_not_trigger_escalation(): void
    {
        $ticket = $this->ticket();
        Carbon::setTestNow(Carbon::parse('2026-11-25 18:00:00', 'Asia/Karachi'));
        $this->assertFalse($ticket->shouldEscalate());
        Carbon::setTestNow(Carbon::parse('2026-11-28 12:00:00', 'Asia/Karachi'));
        $this->assertFalse($ticket->shouldEscalate());
    }

    public function test_only_working_minutes_are_counted_across_the_weekend(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 09:30:00', 'Asia/Karachi'));
        $ticket = $this->ticket(['escalation_time_start' => '2026-11-20 16:30:00']);
        $this->assertTrue($ticket->shouldEscalate());
        $this->assertSame(60, $ticket->getMinutesSinceCreatedDuringBusinessHours($ticket->escalation_time_start));
        SlaPolicy::first()->update(['urgent_first_response_time' => 61]);
        $this->assertFalse($ticket->shouldEscalate());
    }

    public function test_calendar_timezone_is_respected(): void
    {
        BusinessHours::find(2)->update(['timezone' => 'UTC']);
        $this->assertFalse($this->ticket()->shouldEscalate()); // Karachi noon is 07:00 UTC, before this calendar opens.
        BusinessHours::find(2)->update(['wednesday_start' => '06:00', 'wednesday_end' => '14:00']);
        $this->assertTrue($this->ticket()->shouldEscalate());
    }

    public function test_round_the_clock_calendar_counts_weekends(): void
    {
        BusinessHours::find(2)->update(['businesshourstype' => '24x7']);
        Carbon::setTestNow(Carbon::parse('2026-11-29 23:00:00', 'Asia/Karachi'));
        $this->assertTrue($this->ticket(['escalation_time_start' => '2026-11-29 22:00:00'])->shouldEscalate());
    }

    public function test_overnight_working_shift_is_supported(): void
    {
        BusinessHours::find(2)->update(['wednesday_start' => '22:00', 'wednesday_end' => '06:00']);
        Carbon::setTestNow(Carbon::parse('2026-11-26 01:00:00', 'Asia/Karachi'));
        $this->assertTrue($this->ticket(['escalation_time_start' => '2026-11-25 23:00:00'])->shouldEscalate());
    }

    public function test_missing_or_invalid_configuration_fails_closed(): void
    {
        $ticket = $this->ticket();
        foreach ([null, 0, -1, 0.5, 1.5, 'invalid'] as $minutes) {
            SlaPolicy::first()->update(['urgent_first_response_time' => $minutes]);
            $this->assertFalse($ticket->shouldEscalate());
        }
        TicketSubType::first()->update(['priority' => 'Unknown']);
        $this->assertFalse($ticket->fresh()->shouldEscalate());
        BusinessHours::query()->delete();
        $this->artisan('tickets:check-escalations')->assertExitCode(1);
        $this->assertSame(2, $ticket->fresh()->assigned_to);
    }

    public function test_missing_policy_stops_job_without_modifying_tickets(): void
    {
        $ticket = $this->ticket();
        SlaPolicy::query()->delete();
        $this->artisan('tickets:check-escalations')->assertExitCode(1);
        $this->assertSame(2, $ticket->fresh()->assigned_to);
    }

    public function test_missing_start_time_uses_creation_time_and_future_times_do_not_escalate(): void
    {
        $ticket = $this->ticket(['escalation_time_start' => null]);
        $ticket->forceFill(['created_at' => '2026-11-25 11:00:00'])->save();
        $this->assertTrue($ticket->shouldEscalate());
        $ticket->escalation_time_start = '2026-11-25 13:00:00';
        $this->assertFalse($ticket->shouldEscalate());
    }

    public function test_dry_run_reports_candidates_without_updates_timelines_or_notifications(): void
    {
        $ticket = $this->ticket();
        $this->artisan('tickets:check-escalations', ['--dry-run' => true])
            ->expectsOutput('Ticket #'.$ticket->id.' would escalate to user #3.')->assertExitCode(0);
        $this->assertSame(2, $ticket->fresh()->assigned_to);
        $this->assertSame(0, TicketTimeline::count());
        Notification::assertNothingSent();
    }

    public function test_repeated_runs_do_not_escalate_twice_until_new_response_window_elapses(): void
    {
        $ticket = $this->ticket();
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->assertSame(3, $ticket->fresh()->assigned_to);
        $this->assertSame(1, TicketTimeline::count());
        Carbon::setTestNow(Carbon::parse('2026-11-25 13:00:00', 'Asia/Karachi'));
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->assertSame(4, $ticket->fresh()->assigned_to);
        $this->assertSame(2, TicketTimeline::count());
    }

    public function test_no_reporting_manager_keeps_ticket_at_current_level(): void
    {
        $ticket = $this->ticket(['assigned_to' => 4]);
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->assertSame(4, $ticket->fresh()->assigned_to);
        $this->assertSame(1, $ticket->fresh()->current_level);
        $this->assertSame(0, TicketTimeline::count());
    }

    public function test_escalation_and_timeline_rollback_together_if_saving_fails(): void
    {
        $ticket = $this->ticket();
        Schema::drop('ticket_timelines');
        $this->artisan('tickets:check-escalations')->assertExitCode(1);
        $this->assertSame(2, $ticket->fresh()->assigned_to);
        $this->assertSame(1, $ticket->fresh()->current_level);
        Notification::assertNothingSent();
    }

    public function test_notification_failure_does_not_undo_or_repeat_escalation(): void
    {
        $ticket = $this->ticket();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Notification service unavailable'));
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->artisan('tickets:check-escalations')->assertExitCode(0);
        $this->assertSame(3, $ticket->fresh()->assigned_to);
        $this->assertSame(1, TicketTimeline::count());
    }

    public function test_due_date_uses_first_response_working_minutes(): void
    {
        $ticket = $this->ticket(['escalation_time_start' => '2026-11-20 16:30:00']);
        $this->assertSame('2026-11-23 09:30:00', $ticket->calculateDueDate()->toDateTimeString());
        BusinessHours::find(2)->update(['monday' => 'off', 'tuesday' => 'off', 'wednesday' => 'off', 'thursday' => 'off', 'friday' => 'off']);
        $this->assertNull($ticket->calculateDueDate());
    }

    public function test_legacy_http_endpoint_uses_the_same_safe_dry_run(): void
    {
        $ticket = $this->ticket();
        $this->getJson('/ticket/escalation/level?dry_run=1')->assertOk()->assertJsonPath('success', true);
        $this->assertSame(2, $ticket->fresh()->assigned_to);
        Notification::assertNothingSent();
    }

    public function test_policy_update_ignores_resolution_fields_and_validates_first_response_minutes(): void
    {
        require_once database_path('migrations/2023_05_02_180827_create_permission_tables.php');
        (new \CreatePermissionTables())->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        \Spatie\Permission\Models\Permission::create(['name' => 'slapolicies.edit', 'guard_name' => 'web']);
        $user = User::find(1);
        $user->givePermissionTo('slapolicies.edit');
        $payload = ['name' => 'Updated SLA', 'urgent_resolution_time' => 1];
        foreach (['urgent', 'high', 'medium', 'low'] as $priority) {
            $payload[$priority.'_first_response_time'] = 120;
            $payload[$priority.'_operational_hours'] = 'business_hours';
            $payload[$priority.'_escalation'] = 'on';
        }
        $this->actingAs($user)->put(route('slapolicies.update', 1), $payload)->assertRedirect(route('slapolicies.index'));
        $this->assertSame('120', SlaPolicy::first()->urgent_first_response_time);
        $this->assertSame(10000, SlaPolicy::first()->urgent_resolution_time);
        $payload['urgent_first_response_time'] = '10m';
        $this->putJson(route('slapolicies.update', 1), $payload)->assertUnprocessable()->assertJsonValidationErrors('urgent_first_response_time');
    }
    public function test_default_calendar_matches_business_hours_screen_without_hardcoded_id(): void
    {
        config(['ticket_escalation.business_hours_id' => null]);
        DB::table('business_hours')->where('id', 2)->update(['id' => 1]);
        $this->assertTrue($this->ticket()->shouldEscalate());
        config(['ticket_escalation.business_hours_id' => 999]);
        $this->assertFalse($this->ticket()->shouldEscalate());
    }

    public function test_web_reassignment_starts_a_fresh_first_response_window(): void
    {
        $ticket = $this->ticket();
        $this->actingAs(User::find(1))->post(route('tickets.assignto'), ['ticket_id' => $ticket->id, 'assign_to' => 3])
            ->assertRedirect(route('tickets.assign'));
        $ticket->refresh();
        $this->assertSame('2026-11-25 12:00:00', $ticket->escalation_time_start);
        $this->assertFalse($ticket->shouldEscalate());
    }

    public function test_api_reassignment_starts_a_fresh_first_response_window(): void
    {
        require_once database_path('migrations/2023_05_02_180827_create_permission_tables.php');
        (new \CreatePermissionTables())->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        \Spatie\Permission\Models\Permission::create(['name' => 'admindashboard.index', 'guard_name' => 'web']);
        $user = User::find(2);
        $user->givePermissionTo('admindashboard.index');
        \Laravel\Sanctum\Sanctum::actingAs($user);
        $ticket = $this->ticket();
        $ticket->prev_assigned = 3;
        $ticket->save();
        $this->postJson('/api/tickets/'.$ticket->id.'/reassign', ['assign_to' => 3])->assertOk()->assertJsonPath('status', true);
        $ticket->refresh();
        $this->assertSame('2026-11-25 12:00:00', $ticket->escalation_time_start);
        $this->assertFalse($ticket->shouldEscalate());
    }
    public function test_scheduler_registers_first_response_check_every_minute_without_overlap(): void
    {
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $matches = array_values(array_filter($events, fn ($event) => str_contains((string) $event->command, 'tickets:check-escalations')));
        $this->assertCount(1, $matches);
        $this->assertSame('* * * * *', $matches[0]->expression);
        $this->assertTrue($matches[0]->withoutOverlapping);
        $this->assertSame(storage_path('logs/ticket_escalations.log'), $matches[0]->output);
    }
}