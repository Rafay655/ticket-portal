<?php

namespace App\Http\Controllers;

use App\Models\TicketSetting;
use App\Models\TicketEscalation;
use App\Models\TicketSubType;
use App\Models\TicketType;
use App\Models\User;
use App\Http\Requests\StoreTicketSubTypeRequest;
use App\Http\Requests\UpdateTicketSubTypeRequest;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class TicketSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        RolesPermissionController::can(['ticketSettings.view']);
        $perPage = $request->input('perpage', 10); // Default to 10 items per page if not specified
        $data = [];
        $data['ticketsettings'] = TicketSetting::with('ticket_type', 'ticket_sub_type')
        ->paginate($perPage)
        ->appends(['perpage' => $perPage]); // Paginate and append 'perpage' to links
        $data['perPage'] = $perPage; // Current per-page value
        return view('ticketsetting.index', $data);
    }
    
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['ticketSettings.create']);
        $data = [];
        $data['tickettypes']    = TicketType::get();
        $data['ticketsubtypes'] = TicketSubType::get();
        return view('ticketsetting.create',$data);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        RolesPermissionController::can(['ticketSettings.create']);
        $input = $request->all();
        $ticket_setting = TicketSetting::create($input);
        foreach ($input['escalation'] as $key => $escalation) {
            $escalation['ticket_setting_id'] = $ticket_setting->id;
            $escalation['level']             = $key;
            TicketEscalation::create($escalation);
        }
        return redirect()->route('ticketsetting.index')->with('success', 'Create successfully!');
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
    public function edit(TicketSetting $ticketsetting)
    {
        RolesPermissionController::can(['ticketSettings.edit']);
        $data = [];
        $data['ticketsetting']  = $ticketsetting;
        $data['tickettypes']    = TicketType::get();
        $data['ticketsubtypes'] = TicketSubType::get();
        return view('ticketsetting.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TicketSetting $ticketsetting)
    {
        RolesPermissionController::can(['ticketSettings.edit']);
        $input = $request->all();
        $ticketsetting->fill($input);
        $ticketsetting->save();
        $ticketsetting->escalations()->delete();
        
        foreach ($input['escalation'] as $key => $escalation) {
            $escalation['ticket_setting_id'] = $ticketsetting->id;
            $escalation['level']             = $key;
            TicketEscalation::create($escalation);
        }
        return redirect()->route('ticketsetting.index')->with('success', 'Create successfully!');
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketSetting $ticketsetting)
    {
        RolesPermissionController::can(['ticketSettings.delete']);
        $ticketsetting->escalations()->delete();
        if ($ticketsetting->delete()) {
            return ['deleted', 'Ticket Setting has been deleted'];
        } else {
            return ['error', 'Could not delete the Ticket Setting'];
        }
    }
}
