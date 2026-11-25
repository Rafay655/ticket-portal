<?php

namespace App\Services;

use App\Models\BusinessHours;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use Carbon\Carbon;

class TicketResponseSla
{
    public function __construct(private BusinessHoursClock $clock)
    {
    }

    public function calendar(): ?BusinessHours
    {
        $id = config('ticket_escalation.business_hours_id');
        return $id ? BusinessHours::find((int) $id) : BusinessHours::orderBy('id')->first();
    }

    public function isConfigured(): bool
    {
        $calendar = $this->calendar();
        return SlaPolicy::first() !== null && $calendar !== null && $this->clock->timezone($calendar) !== null;
    }

    private function settings(Ticket $ticket): ?array
    {
        $priority = strtolower(trim((string) $ticket->ticketsubtype?->priority));
        if (!in_array($priority, ['urgent', 'high', 'medium', 'low'], true)) return null;
        $policy = SlaPolicy::first();
        $calendar = $this->calendar();
        if (!$policy || !$calendar) return null;
        $minutes = $policy->{$priority.'_first_response_time'};
        if (!is_numeric($minutes) || (float) $minutes < 1 || floor((float) $minutes) !== (float) $minutes) return null;
        // The policy screen currently supports Business hours only.
        if ($policy->{$priority.'_operational_hours'} !== 'business_hours') return null;
        return [
            'minutes' => (int) $minutes,
            'enabled' => in_array(strtolower((string) $policy->{$priority.'_escalation'}), ['on', '1', 'true'], true),
            'calendar' => $calendar,
        ];
    }

    public function shouldEscalate(Ticket $ticket): bool
    {
        if (strtolower(trim((string) $ticket->status)) !== 'open') return false;
        $settings = $this->settings($ticket);
        if (!$settings || !$settings['enabled'] || !$this->clock->isWorkingTime($settings['calendar'])) return false;
        $start = $ticket->escalation_time_start ?: $ticket->created_at;
        if (!$start) return false;
        // A response from the current assignee in this assignment cycle satisfies first response.
        if ($ticket->comments()->where('commenter', $ticket->assigned_to)->where('created_at', '>=', $start)->exists()) return false;
        return $this->clock->elapsedMinutes($settings['calendar'], $start) >= $settings['minutes'];
    }

    public function dueAt(Ticket $ticket): ?Carbon
    {
        $settings = $this->settings($ticket);
        if (!$settings) return null;
        return $this->clock->addMinutes($settings['calendar'], $ticket->escalation_time_start ?: $ticket->created_at, $settings['minutes']);
    }
}
