<?php

namespace App\Http\Controllers;

use App\Models\TicketSubType;
use App\Models\Priority;
use App\Models\TicketType;
use App\Models\TicketSetting;
use App\Services\TicketHierarchy;
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
        $perPage = \App\Support\ListingFilters::perPage($request);
        $ticketsubtypes = \App\Support\ListingFilters::paginate(TicketSubType::with('category.ticketType'), $request, ['name', 'priority'], ['category' => ['name', 'code'], 'category.ticketType' => ['name']]);
        return view('ticketsubtypes.index', compact('ticketsubtypes', 'perPage'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['ticketSubType.create']);
        $data = [];
        $data['priorities'] = Priority::get();
        $data['tickettypes'] = TicketType::orderBy('name')->get();
        $data['hierarchyData'] = app(TicketHierarchy::class)->options();
        return view('ticketsubtypes.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketSubTypeRequest $request)
    {
        RolesPermissionController::can(['ticketSubType.create']);
        $input = $request->safe()->only(['name', 'priority', 'ticket_category_id']);
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
        $data['tickettypes'] = TicketType::orderBy('name')->get();
        $data['hierarchyData'] = app(TicketHierarchy::class)->options();
        return view('ticketsubtypes.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketSubTypeRequest $request, TicketSubType $ticketsubtype)
    {
        RolesPermissionController::can(['ticketSubType.edit']);
        $input = $request->safe()->only(['name', 'priority', 'ticket_category_id']);
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
        if ($ticketsubtype->tickets()->exists() || TicketSetting::where('ticket_sub_type_id', $ticketsubtype->id)->exists()) {
            return ['error', 'This Ticket Sub-Category cannot be deleted because it is used by tickets or Ticket Settings.'];
        }
        if ($ticketsubtype->delete()) {
            return ['deleted', 'Ticket Sub-Category has been deleted'];
        } else {
            return ['error', 'Could not delete the Ticket Sub-Category'];
        }
    }
}
