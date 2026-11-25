<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use App\Models\TicketType;
use Illuminate\Http\JsonResponse;

class TicketTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(TicketType::orderBy('name')->get(['id', 'name']));
    }

    public function categories(TicketType $ticketType): JsonResponse
    {
        return response()->json($ticketType->categories()->orderBy('name')->get(['id', 'name', 'code', 'ticket_type_id']));
    }

    public function categorySubCategories(TicketCategory $ticketCategory): JsonResponse
    {
        abort_unless($ticketCategory->ticket_type_id, 404, 'This Ticket Category has not been assigned to a Ticket Type.');
        return response()->json($ticketCategory->subCategories()->orderBy('name')->get(['id', 'name', 'priority', 'ticket_category_id']));
    }

    public function subTypes(TicketType $ticketType): JsonResponse
    {
        // Backward-compatible endpoint for old mobile clients; only mapped children are selectable.
        return response()->json($ticketType->subTypes()->orderBy('ticket_sub_types.name')
            ->get(['ticket_sub_types.id', 'ticket_sub_types.name', 'ticket_sub_types.priority', 'ticket_sub_types.ticket_category_id'])
            ->map(fn ($subCategory) => $subCategory->only(['id', 'name', 'priority', 'ticket_category_id'])));
    }
}
