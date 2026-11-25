<?php

namespace App\Services;

use App\Models\BusinessHours;
use Carbon\Carbon;
use DateTimeZone;
use Throwable;

class BusinessHoursClock
{
    public function timezone(BusinessHours $calendar): ?string
    {
        $timezone = preg_split('/\s+/', trim($calendar->timezone ?: config('app.timezone')))[0];
        try {
            new DateTimeZone($timezone);
            return $timezone;
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function interval(BusinessHours $calendar, Carbon $date): ?array
    {
        $day = strtolower($date->format('l'));
        if (!in_array(strtolower((string) $calendar->{$day}), ['on', '1', 'true'], true)) {
            return null;
        }
        $times = $calendar->getStartEndTimesForDay($day);
        foreach (['start', 'end'] as $key) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', (string) $times[$key])) {
                return null;
            }
        }
        $start = $date->copy()->setTimeFromTimeString($times['start']);
        $end = $date->copy()->setTimeFromTimeString($times['end']);
        if ($end->equalTo($start)) {
            return null;
        }
        if ($end->lessThan($start)) {
            $end->addDay();
        }
        return [$start, $end];
    }

    public function isWorkingTime(BusinessHours $calendar, ?Carbon $now = null): bool
    {
        $timezone = $this->timezone($calendar);
        if ($timezone === null) return false;
        $now = ($now ?? Carbon::now())->copy()->setTimezone($timezone);
        if ($calendar->businesshourstype === '24x7') return true;
        foreach ([$now->copy()->startOfDay()->subDay(), $now->copy()->startOfDay()] as $date) {
            $interval = $this->interval($calendar, $date);
            if ($interval && $now->greaterThanOrEqualTo($interval[0]) && $now->lessThanOrEqualTo($interval[1])) return true;
        }
        return false;
    }

    public function elapsedMinutes(BusinessHours $calendar, $start, ?Carbon $now = null): int
    {
        $timezone = $this->timezone($calendar);
        if ($timezone === null || !$start) return 0;
        $start = Carbon::parse($start, config('app.timezone'))->setTimezone($timezone);
        $now = ($now ?? Carbon::now())->copy()->setTimezone($timezone);
        if ($start->greaterThanOrEqualTo($now)) return 0;
        if ($calendar->businesshourstype === '24x7') return (int) $start->diffInMinutes($now);
        $seconds = 0;
        for ($date = $start->copy()->startOfDay()->subDay(); $date->lessThanOrEqualTo($now); $date->addDay()) {
            $interval = $this->interval($calendar, $date);
            if (!$interval) continue;
            $from = $start->greaterThan($interval[0]) ? $start : $interval[0];
            $to = $now->lessThan($interval[1]) ? $now : $interval[1];
            if ($from->lessThan($to)) $seconds += $from->diffInSeconds($to);
        }
        return intdiv((int) $seconds, 60);
    }

    public function addMinutes(BusinessHours $calendar, $start, int $minutes): ?Carbon
    {
        $timezone = $this->timezone($calendar);
        if ($timezone === null || !$start || $minutes < 1) return null;
        $start = Carbon::parse($start, config('app.timezone'))->setTimezone($timezone);
        if ($calendar->businesshourstype === '24x7') return $start->addMinutes($minutes);
        // Avoid an endless loop if no usable working day has been configured.
        $hasWork = false;
        for ($i = 0; $i < 7; $i++) {
            if ($this->interval($calendar, $start->copy()->startOfDay()->addDays($i))) $hasWork = true;
        }
        if (!$hasWork) return null;
        $remaining = $minutes * 60;
        for ($date = $start->copy()->startOfDay()->subDay(); ; $date->addDay()) {
            $interval = $this->interval($calendar, $date);
            if (!$interval) continue;
            $from = $start->greaterThan($interval[0]) ? $start->copy() : $interval[0];
            if ($from->greaterThanOrEqualTo($interval[1])) continue;
            $available = $from->diffInSeconds($interval[1]);
            if ($available >= $remaining) return $from->addSeconds($remaining);
            $remaining -= $available;
        }
    }
}
