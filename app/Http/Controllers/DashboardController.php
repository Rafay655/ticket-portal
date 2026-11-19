<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ReportApiController;
use App\Http\Resources\HazardCollection;
use App\Http\Resources\IEAuditAnswersAttachementsCollection;
use App\Http\Resources\IEAuditAnswersCollection;
use App\Http\Resources\IncidentCollection;
use App\Models\FirePropertyDamage;
use App\Models\Hazard;
use App\Models\Injury;
use App\Models\InternalExternalAuditAnswer;
use App\Models\InternalExternalAuditClause;
use App\Models\NearMiss;
use App\Models\TicketSubType;
use App\Models\TicketType;
use App\Models\UnsafeBehavior;
use App\Models\User;
use App\Models\Employee;
use App\Notifications\Welcome;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
// use Spatie\Permission\Models\Role;
// use Spatie\Permission\Models\Permission;
class DashboardController extends Controller
{

    public function index(Request $request)
    {
        $user         = Auth::user();
        $userId       = $user->id;
        $isSuperAdmin = $user->can('admindashboard.index');
        $isRegional   = ! $isSuperAdmin && $user->designation_id == 25;

        // 1) Build the base query
        $baseQuery = Ticket::query();

        // 2) If you're a Regional head, scope to tickets your subordinates created
        if ($isRegional) {
            $me = Employee::where('user_id', $userId)->first();
            if ($me) {
                $subs = $this->getAllSubordinates($me->employee_id);
                $subUserIds = collect($subs)->pluck('user_id')->toArray();

                // only tickets requested by any of your subordinates
                $baseQuery->whereIn('requested_by', $subUserIds);
            }
        }
        // 3) If a normal user, only your assigned tickets
        elseif (! $isSuperAdmin) {
            $baseQuery->where('assigned_to', $userId);
        }
        /* ----------------------------------------------------------------
         |  RECENT TICKETS
         '----------------------------------------------------------------*/
        $lastTickets = (clone $baseQuery)
            ->with(['tickettype','ticketsubtype','comments','requestedby','assignto'])
            ->latest()
            ->take(10)
            ->get();

        $lastTicketsClosed = (clone $baseQuery)
            ->with(['tickettype','ticketsubtype','comments','requestedby','assignto'])
            ->where('status', 'Feedback')
            ->latest()
            ->take(10)
            ->get();

        /* ----------------------------------------------------------------
         |  MONTHLY INCIDENT STATS
         '----------------------------------------------------------------*/
        $currentYear = Carbon::now()->year;

        $rawStats = (clone $baseQuery)
            ->selectRaw('MONTH(created_at)  AS m')
            ->selectRaw('status')
            ->selectRaw('COUNT(*) AS cnt')
            ->whereYear('created_at', $currentYear)
            ->groupBy(DB::raw('MONTH(created_at)'), 'status')
            ->orderByRaw('MONTH(created_at)')
            ->get();

        // Init array 1-12 with zeroes
        $incidentStatsByYear = array_fill(1, 12, [
            'Open'        => 0,
            'in_progress' => 0,
            'Closed'      => 0,
            'Feedback'    => 0,
        ]);
        foreach ($rawStats as $st) {
            $incidentStatsByYear[$st->m][$st->status] = $st->cnt;
        }

        
        $totalTickets = (clone $baseQuery)->count();

        $typeCounts = (clone $baseQuery)
            ->with('tickettype:id,name')
            ->select('tickettype_id', DB::raw('COUNT(*) AS ticket_count'))
            ->groupBy('tickettype_id')
            ->get();

        $ticketTypePercentages = $typeCounts->map(function ($row) use ($totalTickets) {
            $row->percentage = $totalTickets ? ($row->ticket_count / $totalTickets) * 100 : 0;
            $row->counter    = $row->ticket_count;
            $name            = $row->tickettype->name ?? '';

        switch ($name) {
            case 'Jazzcash':
                $row->color = '#a80001';
                break;
            case 'HRPSP POC':
                $row->color = '#0a3aa9';
                break;
        
            default:
                $row->color = sprintf('#%06X', random_int(0, 0xFFFFFF));
        }
        
        return $row;

        });

      
        $subTypeCounts = (clone $baseQuery)
            ->with('ticketsubtype:id,name')
            ->select('ticketsubtype_id', DB::raw('COUNT(*) AS ticket_count'))
            ->groupBy('ticketsubtype_id')
            ->get();

        $ticketSubTypePercentages = $subTypeCounts->map(function ($row) use ($totalTickets) {
            $row->percentage = $totalTickets ? ($row->ticket_count / $totalTickets) * 100 : 0;
            $row->counter    = $row->ticket_count;
            $row->color      = sprintf('#%06X', random_int(0, 0xFFFFFF));
            return $row;
        });

      
        $incidentStatsAdmin = [
            'open'        => (clone $baseQuery)->where('status', 'Open')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'closed'      => (clone $baseQuery)->where('status', 'Closed')->count(),
            'feedback'    => (clone $baseQuery)->where('status', 'Feedback')->count(),
        ];


        /* ----------------------------------------------------------------
         |  REGULAR EMPLOYEE EXTRAS (priority chart & own counts)
         '----------------------------------------------------------------*/
        if (! $isSuperAdmin && ! $isRegional) {
              $total_tickets = Ticket::where('tickets.requested_by', $userId)->count();

            $incidentStats = [
            'open' => Ticket::where('requested_by', $userId)->where('status', 'Open')->count(),
            'in_progress' => Ticket::where('requested_by', $userId)->where('status', 'in_progress')->count(),
            'closed' => Ticket::where('requested_by', $userId)->where('status', 'Closed')->count(),
            'feedback' => Ticket::where('requested_by', $userId)->where('status', 'Feedback')->count(),
        ];

            $tickets = Ticket::where('requested_by', $userId)->paginate(3);

            $priorityCounts = DB::table('tickets')
                ->join('ticket_sub_types', 'tickets.ticketsubtype_id', '=', 'ticket_sub_types.id')
                ->select('ticket_sub_types.priority', DB::raw('count(*) as total'))
                ->where('tickets.requested_by', $userId)
                ->groupBy('ticket_sub_types.priority')
                ->get();

            $priorityData = [
            'Urgent' => 0,
            'High' => 0,
            'Medium' => 0,
            'Low' => 0,
        ];

            foreach ($priorityCounts as $count) {
            $priorityData[$count->priority] = $count->total;
        }

            return view('dashboard.index', compact(
            'incidentStats',
            'total_tickets',
            'tickets',
            'priorityData'
        ));
        }

        return view('dashboard.index', compact(
            'lastTickets',
            'lastTicketsClosed',
            'incidentStatsByYear',
            'ticketTypePercentages',
            'ticketSubTypePercentages',
            'incidentStatsAdmin'
        ));
    }
 public function getAllSubordinates($managerId): array
    {
        $all    = [];
        $queue  = [$managerId];

        while (! empty($queue)) {
            $current = array_shift($queue);

            $subs = Employee::where('report_to', $current)->get();
            foreach ($subs as $e) {
                $all[]   = $e;
                $queue[] = $e->employee_id;
            }
        }

        return $all;
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}