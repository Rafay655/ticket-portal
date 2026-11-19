<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketType;
use Illuminate\Http\JsonResponse;

class TicketTypeController extends Controller
{
    // GET /api/ticket-types  →  [ { id, name } ]
    public function index(): JsonResponse
    {
        $types = TicketType::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($types, 200);
    }

    // GET /api/ticket-types/{ticketType}/sub-types  →  [ { id, name } ]
   public function subTypes(\App\Models\TicketType $ticketType): \Illuminate\Http\JsonResponse
{
    $subTypes = $ticketType->subTypes()
        ->select('ticket_sub_types.id', 'ticket_sub_types.name') // qualify to avoid ambiguity
        ->distinct()
        ->orderBy('ticket_sub_types.name')
        ->get();

    return response()->json($subTypes, 200);
}

}
