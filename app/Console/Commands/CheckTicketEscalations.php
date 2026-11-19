<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\TicketEscalated;
use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\BusinessHours;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckTicketEscalations extends Command
{
   
    protected $signature = 'tickets:check-escalations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and escalate tickets based on business hours and escalation rules';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Log::info('The tickets:check-escalations command ran successfully.');

        try {
            if (!$this->isWorkingTime()) {
                // Log and skip the process
                $this->info('Not in working hours or on a non-working day. Skipping ticket escalation.');
                return;
            }

            // Fetch unresolved tickets
           $tickets = Ticket::whereIn('status', ['open', 'in_progress'])->get();

            if ($tickets->isEmpty()) {
                Log::info('No open tickets to escalate.');
                return;
            }

            foreach ($tickets as $ticket) {
                try {
                    if ($ticket->shouldEscalate()) {
                        $this->info('Ticket #' . $ticket->id . ' should be escalated.');
                        $this->escalateTicket($ticket);
                    } else {
                        $this->info('Ticket #' . $ticket->id . ' does not need escalation.');
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing ticket #' . $ticket->id . ': ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in handle method: ' . $e->getMessage());
        }
    }

    /**
     * Check if the current time is within working hours.
     */
    protected function isWorkingTime()
    {
        try {
            $now = Carbon::now();
            $businessHours = BusinessHours::find(2); // Make sure this ID exists

            // Check if the businessHours record was found
            if (!$businessHours) {
                $this->info('Error: Business hours not found with ID 2.');
                return false;
            }

            // Log the business hours info for debugging
            $this->info('Business hours record found: ' . $businessHours->id);

            // Fetch the working days
            $workingDays = $businessHours->getWorkingDays();
            $currentDay = strtolower($now->format('l'));
            $currentTime = $now->format('H:i');
            $dayTimes = $businessHours->getStartEndTimesForDay($currentDay);

            // Check if today is a working day
            if (!in_array($currentDay, $workingDays)) {
                $this->info('Error: Today is not a working day (' . $currentDay . ').');
                return false;
            }

            // Check if the current time is within working hours
            if (!($currentTime >= $dayTimes['start'] && $currentTime <= $dayTimes['end'])) {
                $this->info('Error: Current time (' . $currentTime . ') is outside of working hours.');
                return false;
            }

            // If everything passes, return true
            return true;
        } catch (\Exception $e) {
            // Log the exception details for debugging
            $this->info('Exception caught in isWorkingTime: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Escalate the ticket to the next level.
     */
    protected function escalateTicket($ticket)
    {
       
        $nextLevel = $ticket->current_level + 1;

        try {
            $nextAssignee = $this->getNextAssignee($ticket->assigned_to);
            // $this->info('Employee == : ' . $nextAssignee);
            if ($nextAssignee) {
                // $this->info('Rafay ');
                $ticket->prev_assigned = $ticket->assigned_to;
                $ticket->current_level = $nextLevel;
                $ticket->assigned_to = $nextAssignee;
                $ticket->escalation_time_start = Carbon::now();
                $ticket->save();
                $userData = [
                    'id' => $ticket->assigned_to,
                    'ticket_id' => $ticket->id, // Assuming you have the ticket ID available here
                ];
                $assignedUser = User::find($ticket->assigned_to);
                $assignedUser->notify(new TicketEscalated($userData));

             
                // Log success
                Log::info('Ticket #' . $ticket->id . ' escalated to user #' . $nextAssignee->user_id);
            } else {
                Log::warning('No next assignee found for ticket #' . $ticket->id);
            }
        } catch (\Exception $e) {
            Log::error('Error escalating ticket #' . $ticket->id . ': ' . $e->getMessage());
        }
    }
   
   
    protected function getNextAssignee($_id)
    {
        try {
            $employee = Employee::where('user_id', $_id)->first();
            // $this->info('Employee: ' . $employee->report_to);
          
            if (!$employee) {
                $this->info('Employee not found with id: ' . $_id);
                return null;
            }

            $next_assign = $employee->report_to;

            if (!$next_assign) {
                $this->info('Next assignee not found for report_to id: ' . $employee);
                return null;

            } else {
                $nextAssignee = Employee::where('employee_id', $next_assign)->first();
                $sending_id = $nextAssignee->user_id;
            }
            // $this->info('Sending id: ' . $sending_id);
            if (!$nextAssignee) {
               $this->info('Next assignee not found for report_to id: ' . $next_assign);
                return null;
            }

            return $sending_id;
        } catch (\Exception $e) {
            $this->info('Error in getNextAssignee: ' . $e->getMessage());
            return null;
        }
    }
}
