<?php

namespace App\Http\Controllers;

use App\Models\TicketType;
use App\Http\Requests\StoreTicketTypeRequest;
use App\Http\Requests\UpdateTicketTypeRequest;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class TicketTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        RolesPermissionController::can(['ticketType.index']);
        $perPage = \App\Support\ListingFilters::perPage($request);
        $tickettypes = \App\Support\ListingFilters::paginate(TicketType::query(), $request, ['name']);
        return view('tickettypes.index', compact('tickettypes', 'perPage'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['ticketType.create']);
        $data = [];
        return view('tickettypes.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketTypeRequest $request)
    {
        RolesPermissionController::can(['ticketType.create']);
        $input = $request->all();
        TicketType::create($input);
        return redirect()->route('tickettypes.index')->with('success', 'Create successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(UpdateTicketTypeRequest $request, TicketType $tickettype)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TicketType $tickettype)
    {
        RolesPermissionController::can(['ticketType.edit']);
        $data = [];
        $data['tickettype'] = $tickettype;
        return view('tickettypes.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketTypeRequest $request, TicketType $tickettype)
    {
        RolesPermissionController::can(['ticketType.edit']);
        $input = $request->all();
        $tickettype->fill($input);
        $tickettype->save();
        return redirect()->route('tickettypes.index')->with('success', 'Update successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketType $tickettype)
    {
        RolesPermissionController::can(['ticketType.delete']);
        if ($tickettype->categories()->exists() || \App\Models\Ticket::where('tickettype_id', $tickettype->id)->exists()) {
            return ['error', 'This Ticket Type cannot be deleted because it has Ticket Categories or tickets.'];
        }
        if ($tickettype->delete()) {
            return ['deleted', 'Ticket type has been deleted'];
        } else {
            return ['error', 'Could not delete the ticket type'];
        }
    }
}
