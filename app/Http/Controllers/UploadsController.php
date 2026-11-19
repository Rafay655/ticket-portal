<?php

namespace App\Http\Controllers;

use App\Imports\EmployeeImport;
use App\Imports\TicketImport;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UploadsController extends Controller
{
    public function index(Request $request)
    {
        
        RolesPermissionController::can(['admindashboard.index']);
        $data = [];
        return view('uploads.index', $data);
    }
    
    public function import(Request $request)
    {
        RolesPermissionController::can(['admindashboard.index']);
        // Validate the file
        $request->validate([
            'employeeFile' => 'required|mimes:xlsx,xls,csv',
        ]);
        
        // Create an instance of UsersImport
        $import = new UsersImport;
        
        // Perform the import
        Excel::import($import, $request->file('employeeFile'));
        
        // Check if there were validation failures
        if (count($import->failures) > 0) {
            return redirect()->back()->with('failures', $import->failures);
        }

        return redirect()->back()->with('success', 'Users imported successfully!');
    }
    public function importTicket(Request $request)
    {
        RolesPermissionController::can(['admindashboard.index']);
        // Validate the file
        $request->validate([
            'employeeFile' => 'required|mimes:xlsx,xls,csv',
        ]);
        
        // Create an instance of UsersImport
        $import = new TicketImport;
        
        // Perform the import
        Excel::import($import, $request->file('employeeFile'));
        
        // Check if there were validation failures
        if (count($import->failures) > 0) {
            return redirect()->back()->with('failures', $import->failures);
        }
        
        return redirect()->back()->with('success', 'Users imported successfully!');
    }
    public function importEmployee(Request $request)
    {
        RolesPermissionController::can(['admindashboard.index']);
        // Validate the file
        $request->validate([
            'employeeFile' => 'required|mimes:xlsx,xls,csv',
        ]);
       
        $import = new EmployeeImport;

        // Perform the import
        Excel::import($import, $request->file('employeeFile'));

    
        
        if ($import->failures()->isNotEmpty()) {
            foreach ($import->failures() as $failure) {
                // You can log the failure details or display them to the user
                logger()->error('Row ' . $failure->row() - 1 . ': ' . implode(', ', $failure->errors()));
            }
        }
        
        return redirect()->back()->with('success', 'Users imported successfully!');
    }
}
