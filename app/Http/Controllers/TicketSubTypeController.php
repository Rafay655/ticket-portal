<?php

namespace App\Http\Controllers;

use App\Models\TicketSubType;
use App\Models\Priority;
use App\Http\Requests\StoreTicketSubTypeRequest;
use App\Http\Requests\UpdateTicketSubTypeRequest;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class TicketSubTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        RolesPermissionController::can(['ticketSubType.index']);
        $perPage = $request->input('perpage', 10); // Default to 10 items per page if not specified
        $data = [];
        $data['ticketsubtypes'] = TicketSubType::paginate($perPage)->appends(['perpage' => $perPage]); 
        // dd($data);
        $data['perPage'] = $perPage; // Current per-page value
        return view('ticketsubtypes.index', $data);
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['ticketSubType.create']);
        $data = [];
        $data['priorities'] = Priority::get();
        return view('ticketsubtypes.create',$data);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketSubTypeRequest $request)
    {
        RolesPermissionController::can(['ticketSubType.create']);
        $input = $request->all();
        TicketSubType::create($input);
        return redirect()->route('ticketsubtypes.index')->with('success', 'Create successfully!');
    }
    
    /**
     * Display the specified resource.
     */
    public function show(UpdateTicketSubTypeRequest $request, TicketSubType $ticketsubtype)
    {
        //
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TicketSubType $ticketsubtype)
    {
        RolesPermissionController::can(['ticketSubType.edit']);
        $data = [];
        $data['ticketsubtype'] = $ticketsubtype;
        $data['priorities'] = Priority::get();
        return view('ticketsubtypes.edit',$data);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketSubTypeRequest $request, TicketSubType $ticketsubtype)
    {
        RolesPermissionController::can(['ticketSubType.edit']);
        $input = $request->all();
        $ticketsubtype->fill($input);
        $ticketsubtype->save();
        return redirect()->route('ticketsubtypes.index')->with('success', 'Update successfully!');
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketSubType $ticketsubtype)
    {
     RolesPermissionController::can(['ticketSubType.delete']);
        if ($ticketsubtype->delete()) {
            return ['deleted', 'Ticket Sub Type has been deleted'];
        } else {
            return ['error', 'Could not delete the Ticket Sub Type'];   
        }
    }
}
