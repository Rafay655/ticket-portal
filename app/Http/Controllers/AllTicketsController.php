<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class AllTicketsController extends Controller
{
    /**
     * Display a listing of all tickets.
     */
    // app/Http/Controllers/TicketController.php

public function index(Request $request)
{
    // Only admins may view
    if (! auth()->user()->can('admindashboard.index')) {
        abort(401, 'Unauthorized');
    }

    $perPageInput = $request->input('perpage', '10');
    $search       = $request->input('search', '');

    // Base query + eager loads
    $query = Ticket::with([
        'tickettype', 'ticketsubtype', 'comments',
        'requestedby', 'assignto'
    ]);

    // Server-side search
    if (trim($search) !== '') {
        $query->where(function($q) use ($search) {
            $q->whereHas('tickettype', function($q2) use ($search) {
                    $q2->where('name','like',"%{$search}%");
                })
                ->orWhereHas('ticketsubtype', function($q2) use ($search) {
                    $q2->where('name','like',"%{$search}%");
                })
                ->orWhereHas('requestedby', function($q2) use ($search) {
                    $q2->where('name','like',"%{$search}%");
                })
                ->orWhereHas('assignto', function($q2) use ($search) {
                    $q2->where('name','like',"%{$search}%");
                })
                ->orWhere('status','like',"%{$search}%")
                ->orWhereDate('created_at',$search);
        });
    }

    // “All” handling
    if ($perPageInput === 'all') {
        $perPage = max(1, $query->count());
    } else {
        $perPage = (int)$perPageInput;
    }

    // Final paginate & append
    $tickets = $query
        ->orderBy('created_at','desc')
        ->paginate($perPage)
        ->appends([
            'perpage' => $perPageInput,
            'search'  => $search,
        ]);

    return view('tickets.showAll', [
        'tickets'    => $tickets,
        'perPage'    => $perPageInput,
        'search'     => $search,
    ]);
}

    
}
