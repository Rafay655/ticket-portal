<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Ticket;
use App\Models\TicketTimeline;
use App\Models\User;
use App\Notifications\TicketEscalated;
use App\Services\TicketResponseSla;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckTicketEscalations extends Command
{
    protected $signature = 'tickets:check-escalations {--dry-run : List overdue tickets without updating or notifying anyone}';
    protected $description = 'Escalate Open tickets that exceed their enabled first-response SLA';

    public function handle(TicketResponseSla $sla): int
    {
        if (!$sla->isConfigured()) {
            $this->error('First-response SLA policy or business calendar is missing/invalid. No tickets were escalated.');
            Log::warning('Ticket escalation skipped: missing SLA policy or configured business calendar.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $escalated = 0;
        $failures = 0;
        Ticket::whereRaw('LOWER(TRIM(status)) = ?', ['open'])->orderBy('id')->chunkById(100, function ($tickets) use ($sla, $dryRun, &$checked, &$escalated, &$failures) {
            foreach ($tickets as $ticket) {
                $checked++;
                try {
                    if (!$sla->shouldEscalate($ticket)) continue;
                    if ($dryRun) {
                        $next = $this->getNextAssignee((int) $ticket->assigned_to);
                        $this->line('Ticket #'.$ticket->id.($next ? ' would escalate to user #'.$next : ' is overdue but has no next reporting manager').'.');
                        continue;
                    }

                    // Recheck under a row lock so overlapping runs cannot escalate twice.
                    $changed = DB::transaction(function () use ($ticket, $sla) {
                        $current = Ticket::whereKey($ticket->id)->lockForUpdate()->first();
                        if (!$current || !$sla->shouldEscalate($current)) return null;
                        $next = $this->getNextAssignee((int) $current->assigned_to);
                        if (!$next) {
                            Log::warning('No next reporting manager for overdue ticket.', ['ticket_id' => $current->id]);
                            return null;
                        }
                        $previous = $current->assigned_to;
                        $current->prev_assigned = $previous;
                        $current->assigned_to = $next;
                        $current->current_level = (int) ($current->current_level ?? 1) + 1;
                        $current->escalation_time_start = now();
                        $current->save();
                        TicketTimeline::create([
                            'ticket_id' => $current->id,
                            'assign_to' => $next,
                            'assign_time' => now(),
                            'assign_type' => 'By Auto Escalation',
                        ]);
                        return $current;
                    }, 3);

                    if ($changed) {
                        $escalated++;
                        $this->info('Ticket #'.$changed->id.' escalated to user #'.$changed->assigned_to.'.');
                        Log::info('Ticket escalated after first-response SLA breach.', ['ticket_id' => $changed->id, 'assigned_to' => $changed->assigned_to]);
                        // Notification failures must not roll back or repeat a successful escalation.
                        try {
                            User::find($changed->assigned_to)?->notify(new TicketEscalated([
                                'id' => $changed->assigned_to,
                                'ticket_id' => $changed->id,
                            ]));
                        } catch (Throwable $exception) {
                            Log::error('Ticket escalated, but notification failed.', ['ticket_id' => $changed->id, 'error' => $exception->getMessage()]);
                        }
                    }
                } catch (Throwable $exception) {
                    $failures++;
                    Log::error('Ticket first-response escalation check failed.', ['ticket_id' => $ticket->id, 'error' => $exception->getMessage()]);
                    $this->error('Ticket #'.$ticket->id.' could not be checked: '.$exception->getMessage());
                }
            }
        });
        $this->info(($dryRun ? 'Dry run: ' : '').'Checked '.$checked.' Open ticket(s); escalated '.$escalated.'.');
        return $failures ? self::FAILURE : self::SUCCESS;
    }

    protected function getNextAssignee(int $userId): ?int
    {
        $employee = Employee::where('user_id', $userId)->first();
        if (!$employee || !$employee->report_to) return null;
        $manager = Employee::where('employee_id', $employee->report_to)->first();
        if (!$manager || !$manager->user_id || (int) $manager->user_id === $userId) return null;
        return User::whereKey($manager->user_id)->exists() ? (int) $manager->user_id : null;
    }
}
