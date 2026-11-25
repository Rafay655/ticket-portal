<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        RolesPermissionController::can(['grade.index']);
        $perPage = \App\Support\ListingFilters::perPage($request);
        $grades = \App\Support\ListingFilters::paginate(Grade::query(), $request, ['name']);
        return view('grades.index', compact('grades', 'perPage'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['grade.create']);
        $data = [];
        return view('grades.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGradeRequest $request)
    {
        RolesPermissionController::can(['grade.create']);
        $input = $request->all();
        Grade::create($input);
        return redirect()->route('grades.index')->with('success', 'Create successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Grade $grade)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Grade $grade)
    {
        RolesPermissionController::can(['grade.edit']);
        $data = [];
        $data['grade'] = $grade;
        return view('grades.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGradeRequest $request, Grade $grade)
    {
        RolesPermissionController::can(['grade.edit']);
        $input = $request->all();
        $grade->fill($input);
        $grade->save();
        return redirect()->route('grades.index')->with('success', 'Update successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grade $grade)
    {
        RolesPermissionController::can(['grade.delete']);
        if ($grade->delete()) {
            return ['deleted', 'Grade has been deleted'];
        } else {
            return ['error', 'Could not delete the grade'];
        }
    }
}
