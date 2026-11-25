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

    $perPageInput = \App\Support\ListingFilters::perPage($request);
    $search       = \App\Support\ListingFilters::search($request);

    // Base query + eager loads
    $query = Ticket::with([
        'tickettype', 'ticketsubtype.category', 'ticketCategory', 'comments',
        'requestedby', 'assignto'
    ]);

    // Server-side search
    \App\Support\ListingFilters::tickets($query, $request);

    // “All” handling
    if ($perPageInput === 'all') {
        $perPage = max(1, $query->count());
    } else {
        $perPage = (int)$perPageInput;
    }

    // Final paginate & append
    $tickets = $query
        ->orderBy('created_at', 'desc')->orderBy('id', 'desc')
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
