<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Http\Requests\StoreRegionRequest;
use App\Http\Requests\UpdateRegionRequest;
use Illuminate\Http\Request;
class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        RolesPermissionController::can(['region.index']);
        $perPage = $request->input('perpage', 10); // Default to 10 items per page if not specified
        $data = [];
        $data['regions'] = Region::with('parent')->paginate($perPage)->appends(['perpage' => $perPage]); // Paginate with 'parent' relation
        $data['perPage'] = $perPage; // Current per-page value
        return view('regions.index', $data);
    }
    
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['region.create']);
        $data = [];
        $data['regions'] = Region::get();
        return view('regions.create', $data);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegionRequest $request)
    {
        RolesPermissionController::can(['region.create']);
        $input = $request->all();
        Region::create($input);
        return redirect()->route('regions.index')->with('success', 'Create successfully!');
    }
    
    /**
     * Display the specified resource.
     */
    public function show(Region $region)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Region $region)
    {
        RolesPermissionController::can(['region.edit']);
        $data = [];
        $data['region'] = $region;
        $data['p_regions'] = Region::get();
        return view('regions.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRegionRequest $request, Region $region)
    {
        RolesPermissionController::can(['region.edit']);
        $input = $request->all();
        $region->fill($input);
        $region->save();
        return redirect()->route('regions.index')->with('success', 'Update successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Region $region)
    {
        RolesPermissionController::can(['region.delete']);
        if ($region->delete()) {
            return ['deleted', 'Region has been deleted'];
        } else {
            return ['error', 'Could not delete the region'];
        }
    }
}
