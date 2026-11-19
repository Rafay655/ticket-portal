<?php

namespace App\Models;
use App\Models\Region;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{

    protected $fillable = [
        'tickettype_id',
        'ticketsubtype_id',
        'description',
        'ticket_attachment',
        'requested_by',
        'assigned_to',
        'status',
        'current_level',
        'completed_at',
        'escalation_time_start'

    ];

    public static function getRouteName()
    {
        return 'employees.show'; //show route name
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tickettype()
    {
        return $this->belongsTo(TicketType::class, 'tickettype_id');
    }

    public function ticketsubtype()
    {
        return $this->belongsTo(TicketSubType::class, 'ticketsubtype_id');
    }

    public function requestedby()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignto()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'ticket_id');
    }

    public function feedback()
    {
        return $this->hasMany(TicketFeedback::class, 'ticket_id');
    }
    public function shouldEscalate()
    {
        $now = Carbon::now();


        $escalation_time_start = $this->escalation_time_start;
        $minutesSinceCreated = $this->getMinutesSinceCreatedDuringBusinessHours($escalation_time_start);

        $subticketPriority = $this->ticketsubtype->priority;

        $slaPolicy = SlaPolicy::first();

        if ($slaPolicy) {
            switch ($subticketPriority) {
                case 'Urgent':
                    $resolutionTime = $slaPolicy->urgent_resolution_time;
                    break;
                case 'High':
                    $resolutionTime = $slaPolicy->high_resolution_time;
                    break;
                case 'Medium':
                    $resolutionTime = $slaPolicy->medium_resolution_time;
                    break;
                case 'Low':
                    $resolutionTime = $slaPolicy->low_resolution_time;
                    break;
                default:
                    $resolutionTime = null;
            }


            if ($minutesSinceCreated >= $resolutionTime) {
                return true;
            } else {
                return false;

            }
        }


    }

    public function getMinutesSinceCreatedDuringBusinessHours($createdAt)
    {
        $totalWorkingMinutes = 0;
        if (is_string($createdAt)) {
            $createdAt = Carbon::parse($createdAt);
        }
        $now = Carbon::now();

        $businessHours = BusinessHours::find(2);
        $workingDays = $businessHours->getWorkingDays();

        $currentDate = $createdAt->copy();

        while ($currentDate->lessThanOrEqualTo($now)) {
            $currentDay = strtolower($currentDate->format('l'));

            if (in_array($currentDay, $workingDays)) {
                $dayTimes = $businessHours->getStartEndTimesForDay($currentDay);
                $businessStartTime = Carbon::parse($dayTimes['start'])->setDate($currentDate->year, $currentDate->month, $currentDate->day);
                $businessEndTime = Carbon::parse($dayTimes['end'])->setDate($currentDate->year, $currentDate->month, $currentDate->day);

                if ($currentDate->isSameDay($createdAt)) {
                    if ($createdAt->between($businessStartTime, $businessEndTime)) {
                        $startTime = $createdAt;
                    } elseif ($createdAt->lessThan($businessStartTime)) {
                        $startTime = $businessStartTime;
                    } else {
                        $currentDate->addDay()->startOfDay();
                        continue;
                    }
                } else {
                    $startTime = $businessStartTime;
                }

                if ($currentDate->isSameDay($now)) {
                    $endTime = $now->lessThan($businessEndTime) ? $now : $businessEndTime;
                } else {
                    $endTime = $businessEndTime;
                }

                if ($startTime->lessThan($endTime)) {
                    $totalWorkingMinutes += $startTime->diffInMinutes($endTime);
                }
            }

            $currentDate->addDay()->startOfDay();
        }

        return $totalWorkingMinutes;
    }
    public function calculateDueDate()
    {
        $now = Carbon::now();

        // Get the SLA policy for resolution times based on priority
        $subticketPriority = $this->ticketsubtype->priority;
        $slaPolicy = SlaPolicy::first();

        if (!$slaPolicy) {
            return null; // Return null if no SLA policy is found
        }

        switch ($subticketPriority) {
            case 'Urgent':
                $resolutionTime = $slaPolicy->urgent_resolution_time;
                break;
            case 'High':
                $resolutionTime = $slaPolicy->high_resolution_time;
                break;
            case 'Medium':
                $resolutionTime = $slaPolicy->medium_resolution_time;
                break;
            case 'Low':
                $resolutionTime = $slaPolicy->low_resolution_time;
                break;
            default:
                $resolutionTime = null;
        }

        if ($resolutionTime === null) {
            return null; // Return null if no resolution time is available for the priority
        }

        // Convert resolution time (in hours) into minutes
        $requiredMinutes = $resolutionTime ;

        // Calculate due date during business hours
        $dueDate = $this->calculateDueDateDuringBusinessHours($this->created_at, $requiredMinutes);

        return $dueDate;
    }

    public function calculateDueDateDuringBusinessHours($createdAt, $requiredMinutes)
    {
        if (is_string($createdAt)) {
            $createdAt = Carbon::parse($createdAt);
        }

        $businessHours = BusinessHours::find(2); // Assuming business hours are stored in the BusinessHours model
        $workingDays = $businessHours->getWorkingDays(); // Get array of working days (e.g., ['monday', 'tuesday', ...])

        $currentDate = $createdAt->copy();
        $remainingMinutes = $requiredMinutes;

        while ($remainingMinutes > 0) {
            $currentDay = strtolower($currentDate->format('l')); // Get the current day of the week in lowercase

            // Check if the current day is a working day
            if (in_array($currentDay, $workingDays)) {
                $dayTimes = $businessHours->getStartEndTimesForDay($currentDay);
                $businessStartTime = Carbon::parse($dayTimes['start'])->setDate($currentDate->year, $currentDate->month, $currentDate->day);
                $businessEndTime = Carbon::parse($dayTimes['end'])->setDate($currentDate->year, $currentDate->month, $currentDate->day);

                // Calculate working minutes for the current day
                if ($currentDate->isSameDay($createdAt)) {
                    // If it's the same day as ticket creation, start from the created time
                    $startTime = $createdAt->greaterThan($businessStartTime) ? $createdAt : $businessStartTime;
                } else {
                    $startTime = $businessStartTime;
                }

                // Calculate end time for today (if it's today, compare with the current time)
                $endTime = $currentDate->isToday() ? Carbon::now()->min($businessEndTime) : $businessEndTime;

                // Calculate the difference in minutes
                $workingMinutes = $startTime->diffInMinutes($endTime, false); // False ensures that negative values aren't returned

                if ($workingMinutes > 0) {
                    if ($workingMinutes >= $remainingMinutes) {
                        // If remaining minutes can be covered in the current day
                        return $startTime->addMinutes($remainingMinutes); // Return the due date
                    } else {
                        // Subtract the minutes worked today and move to the next day
                        $remainingMinutes -= $workingMinutes;
                    }
                }
            }

            // Move to the next day and reset time to start of day
            $currentDate->addDay()->startOfDay();
        }

        return $currentDate; // This will be the calculated due date
    }



}
