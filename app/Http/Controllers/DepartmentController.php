<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Http\Requests\StoreDeparmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        RolesPermissionController::can(['department.index']);
        $perPage = $request->input('perpage', 10); // Default to 10 items per page
        
        // Fetch paginated departments
        $departments = Department::paginate($perPage)->appends(['perpage' => $perPage]);
    
        return view('departments.index', compact('departments', 'perPage'));
    }
    
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['department.create']);
        $data = [];
        return view('departments.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeparmentRequest $request)
    {
        RolesPermissionController::can(['department.create']);
        $input = $request->all();
        Department::create($input);
        return redirect()->route('departments.index')->with('success', 'Create successfully!');
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
    public function edit(Department $department)
    {
        RolesPermissionController::can(['department.edit']);
        $data = [];
        $data['department'] = $department;
        return view('departments.edit',$data);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        RolesPermissionController::can(['department.edit']);
        $input = $request->all();
        $department->fill($input);
        $department->save();
        return redirect()->route('departments.index')->with('success', 'Update successfully!');
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        RolesPermissionController::can(['department.delete']);
        if ($department->delete()) {
            return ['deleted', 'Department has been deleted'];
        } else {
            return ['error', 'Could not delete the Department'];
        }
    }
}
