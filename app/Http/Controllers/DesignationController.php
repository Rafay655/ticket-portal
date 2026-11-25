<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Http\Requests\StoreDesignationRequest;
use App\Http\Requests\UpdateDesignationRequest;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        RolesPermissionController::can(['designation.index']);
        $perPage = \App\Support\ListingFilters::perPage($request);
        $designations = \App\Support\ListingFilters::paginate(Designation::query(), $request, ['name']);
        return view('designations.index', compact('designations', 'perPage'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['designation.create']);
        $data = [];
        return view('designations.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDesignationRequest $request)
    {
        RolesPermissionController::can(['designation.create']);
        $input = $request->all();
        Designation::create($input);
        return redirect()->route('designations.index')->with('success', 'Create successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(UpdateDesignationRequest $request, Designation $designation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Designation $designation)
    {
        RolesPermissionController::can(['designation.edit']);
        $data = [];
        $data['designation'] = $designation;
        return view('designations.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDesignationRequest $request, Designation $designation)
    {
        RolesPermissionController::can(['designation.edit']);
        $input = $request->all();
        $designation->fill($input);
        $designation->save();
        return redirect()->route('designations.index')->with('success', 'Update successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Designation $designation, $channel = "web")
    {
        RolesPermissionController::can(['designation.delete']);
        if ($designation->delete()) {
            if ($channel === 'api') {
                return ApiResponseController::success('Designation has been delete');
            } else {
                return ['deleted', 'Designation has been deleted'];
            }
        } else {
            if ($channel === 'api') {
                return ApiResponseController::error('Could not delete the Designation.');
            } else {
                return ['error', 'Could not delete the Designation'];
            }
        }
    }
}
