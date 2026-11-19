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
        $perPage = $request->input('perpage', 10); // Default to 10 items per page if not specified
        $data = [];
        $data['tickettypes'] = TicketType::paginate($perPage)->appends(['perpage' => $perPage]); // Paginate and append 'perpage' to links
        $data['perPage'] = $perPage; // Current per-page value
        return view('tickettypes.index', $data);
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
        if ($tickettype->delete()) {
            return ['deleted', 'Ticket type has been deleted'];
        } else {
            return ['error', 'Could not delete the ticket type'];
        }
    }
}
