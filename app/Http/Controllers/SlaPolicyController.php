<?php

namespace App\Http\Controllers;

use App\Models\Priority;
use App\Models\BusinessHours;
use App\Models\SlaPolicy;
use DateTimeZone;
use DateTime;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class SlaPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request,)
    {
        RolesPermissionController::can(['slapolicies.index']);
        $data = [];
        $data['slapolicy'] = SlaPolicy::first();
        return view('slapolicy.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['slapolicies.create']);
        $data = [];
        $data['priorities'] = Priority::get();
        return view('slapolicy.create',$data);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        RolesPermissionController::can(['slapolicies.create']);
        $input = $this->validatedPolicy($request);
        // Preserve compatibility with legacy non-null database columns; these are no longer SLA targets.
        foreach (['urgent', 'high', 'medium', 'low'] as $priority) {
            $input[$priority.'_resolution_time'] = 0;
        }
        SlaPolicy::create($input);
        return redirect()->route('slapolicies.index')->with('success', 'Create successfully!');
    }
    
    /**
     * Display the specified resource.
     */
    public function show(UpdateDepartmentRequest $request, Designation $designation)
    {
        //
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        RolesPermissionController::can(['slapolicies.edit']);
        $data = [];
        $data['slapolicy']  = SlaPolicy::find($id);
        $data['priorities'] = Priority::get();
        return view('slapolicy.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        RolesPermissionController::can(['slapolicies.edit']);
        $input      = $this->validatedPolicy($request);
        $slapolicy  = SlaPolicy::find($id);
        $slapolicy->fill($input);
        $slapolicy->save();
        return redirect()->route('slapolicies.index')->with('success', 'Update successfully!');
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SlaPolicy $slapolicy)
    {
        RolesPermissionController::can(['slapolicies.delete']);
        if ($slapolicy->delete()) {
            return redirect()->route('slapolicies.index')->with('success', 'Delete successfully!');
        } else {
            return redirect()->route('slapolicies.index')->with('error', 'Something went wrong!');
        }
    }

    private function validatedPolicy(Request $request): array
    {
        $rules = ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string']];
        foreach (['urgent', 'high', 'medium', 'low'] as $priority) {
            $rules[$priority.'_first_response_time'] = ['required', 'integer', 'min:1'];
            $rules[$priority.'_operational_hours'] = ['required', 'in:business_hours'];
            $rules[$priority.'_escalation'] = ['required', 'in:on,off'];
        }
        return $request->validate($rules);
    }
}
