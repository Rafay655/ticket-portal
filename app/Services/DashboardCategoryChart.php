<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardCategoryChart
{
    public function counts(Builder $scope): Collection
    {
        $tickets = (clone $scope)->select('tickets.tickettype_id', 'tickets.ticketsubtype_id', 'tickets.ticket_category_id');
        $rows = DB::query()->fromSub($tickets->toBase(), 'visible_tickets')
            ->leftJoin('ticket_categories as saved_category', 'saved_category.id', '=', 'visible_tickets.ticket_category_id')
            ->leftJoin('ticket_sub_types as leaf', 'leaf.id', '=', 'visible_tickets.ticketsubtype_id')
            ->leftJoin('ticket_categories as legacy_category', function ($join) {
                $join->on('legacy_category.id', '=', 'leaf.ticket_category_id')
                    ->on('legacy_category.ticket_type_id', '=', 'visible_tickets.tickettype_id');
            })
            ->selectRaw("COALESCE(saved_category.id, legacy_category.id) as category_id, COALESCE(saved_category.name, legacy_category.name, 'Not assigned') as name, COUNT(*) as counter")
            ->groupByRaw("COALESCE(saved_category.id, legacy_category.id), COALESCE(saved_category.name, legacy_category.name, 'Not assigned')")
            ->orderByDesc('counter')->orderBy('name')->get();
        $palette = ['#0a3aa9', '#7c3aed', '#0891b2', '#16a34a', '#ea580c', '#be185d'];
        $total = $rows->sum('counter');
        return $rows->map(function ($row) use ($palette, $total) {
            $row->counter = (int) $row->counter;
            $row->color = $row->category_id === null ? '#94a3b8' : $palette[((int) $row->category_id - 1) % count($palette)];
            $row->percentage = $total ? $row->counter / $total * 100 : 0;
            return $row;
        });
    }
}
