<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Grade;
use App\Models\Region;

use App\Models\User;
use App\Models\Employee;
use App\Http\Requests\StoreEmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Log;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\UserCreatedNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\EmployeeExport;
use Maatwebsite\Excel\Facades\Excel;
class EmployeesController extends Controller{
   // app/Http/Controllers/EmployeeController.php


public function index(Request $request)
{
    RolesPermissionController::can(['employee.index']);

    $search       = $request->input('search', '');
    $perpageInput = $request->input('perpage', '10');

    // 1) Build the base query
   if (auth()->user()->can('admindashboard.index') || in_array(auth()->id(), [531, 532])) {

        $query = Employee::with('user.roles');
    } else {
        $me     = Employee::where('user_id', auth()->id())->first();
        if (! $me) {
            // no employee record for this user — show nothing
            $empty = new LengthAwarePaginator([], 0, 10);
            return view('employees.index', [
                'employees' => $empty,
                'perPage'   => 10,
            ]);
        }

        // get everyone who reports (direct/indirect) + yourself
        $subIds     = $this->getAllSubordinateIds($me->employee_id);
        $visibleIds = array_merge([ $me->employee_id ], $subIds);

        $query = Employee::whereIn('employee_id', $visibleIds)
                         ->with('user.roles');
    }

    // 2) Apply search filter if any
    if (trim($search) !== '') {
        $query->where(function($q) use ($search) {
            $q->where('employee_id', 'like', "%{$search}%")
              ->orWhere('name',        'like', "%{$search}%")
              ->orWhere('email',       'like', "%{$search}%")
              ->orWhereHas('user.roles', function($qr) use ($search) {
                  $qr->where('name', 'like', "%{$search}%");
              });
        });
    }

    // 3) Resolve “all” vs numeric
    if ($perpageInput === 'all') {
        $perPage = max(1, $query->count());
    } else {
        $perPage = (int) $perpageInput;
    }

    // 4) Paginate & append params
    $employees = $query
        ->orderBy('updated_at','desc')
        ->paginate($perPage)
        ->appends([
            'perpage' => $perpageInput,
            'search'  => $search,
        ]);

    return view('employees.index', [
        'employees' => $employees,
        'perPage'   => $perpageInput,
    ]);
}

private function getAllSubordinateIds($managerId): array
{
    $ids   = [];
    $queue = [$managerId];

    while (!empty($queue)) {
        $current = array_shift($queue);

        // get direct reports of $current
        $direct = Employee::where('report_to', $current)
                          ->pluck('employee_id')
                          ->toArray();

        foreach ($direct as $subId) {
            if (! in_array($subId, $ids)) {
                $ids[]   = $subId;
                $queue[] = $subId;
            }
        }
    }

    return $ids;
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            RolesPermissionController::can(['employee.create']);
            // Fetch data
            $data = [];
            $data['departments'] = Department::get();
            $data['designations'] = Designation::get();
            $data['roles'] = Role::select('name', 'id')->get();
            $data['grades'] = Grade::select('name', 'id')->get();
            $data['regions'] = Region::where('p_region_id', 0)->get();
            $data['sub_regions'] = Region::where('p_region_id', '>', 0)->get();
            
            $data['employees'] = Employee::with(['user.roles:id,name'])
                ->get()
                ->map(function ($employee) {
                    return [
                        'employee_id' => $employee->employee_id,
                        'name' => $employee->name,
                        'role' => $employee->user->roles->pluck('name')->first() ?? 'No role assigned',
                        'region_ids' => is_array($employee->region_ids) ? implode(',', $employee->region_ids) : $employee->region_ids,
                        'sub_region_id' => $employee->sub_region_id,
                    ];
                });

            return view('employees.create', $data);
        } catch (\Exception $e) {

            return redirect()->route('employees.index')->with('failed', 'An error occurred while loading the form.');
        }
    }


    public function store(StoreEmployeeRequest $request, $channel = "web")
    {
        try {
            // Check permissions
            RolesPermissionController::can(['employee.create']);
          
            // Validate core fields + regions/sub-region
            $request->validate([
                'employee_id' => 'required|unique:employees,employee_id',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'roles' => 'required',
                
                'report_to' => 'nullable|exists:employees,employee_id',
            ]);
           $regions_id = array_map(
                'intval',
                $request->input('region_id', [])
            );
            // Build User data (only what belongs in users table)
            $userData = $request->only(['name', 'email']);
            $plainPassword = $this->generateStrongPassword();
            $userData['password'] = Hash::make($plainPassword);
            $userData['status'] = 1;
            $userData['department_id'] = $request->department_id;
            $userData['designation_id'] = $request->designation_id;
            $userData['user_type'] = 'employee';
            $userData['region_ids'] =  $regions_id;
          
            // Image on user (if you store it here)
            if ($request->hasFile('image')) {
                $filename = time() . '.' . $request->image->getClientOriginalExtension();
                $request->image->move(public_path('images/profile'), $filename);
                $userData['image'] = $filename;
            }
            // Create the User
            // Build Employee data
            $user = User::create($userData);

            // Assign roles
            $user->syncRoles($request->roles);
            $employeeData = [
                'employee_id' => $request->employee_id,
                'name' => $request->name,
                'email' => $request->email,
                'secondary_email' => $request->secondary_email,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'grade' => $request->grade,
                'mobile' => $request->mobile,
                'user_id' => $user->id,
                'region_ids' =>  $regions_id,   // JSON array
                'sub_region_id' => $request->input('sub_region_id'),
                'report_to' => $request->input('report_to'),
                'status' => 1,
            ];
         
           
           
           

            // Carry over image if stored on employees table instead
            if (!empty($userData['image'])) {
                $employeeData['image'] = $userData['image'];
            }

            // Create the Employee
            $employee = Employee::create($employeeData);

            // Send welcome email
            try {
                Mail::to($user->email)
                    ->send(new UserCreatedNotification($user, $plainPassword));
            } catch (\Exception $e) {

                return redirect()->back()->with('failed', 'Employee created, but failed to send email.');
            }

            // Return success message
            return redirect()
                ->route('employees.index')
                ->with('success', 'Employee created successfully!');
        } catch (\Exception $e) {

            return redirect()->back()->with('failed', 'An error occurred while creating the employee.',$e);
        }
    }



    private function generateStrongPassword($length = 8)
    {
        // Ensure minimum length is 8
        if ($length < 8) {
            $length = 8;
        }

        // Characters pools
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()-_=+';

        // Generate at least one character from each pool
        $password = substr(str_shuffle($lowercase), 0, 1) .
            substr(str_shuffle($uppercase), 0, 1) .
            substr(str_shuffle($numbers), 0, 1) .
            substr(str_shuffle($specialChars), 0, 1);

        // Generate the remaining characters randomly
        $remainingLength = $length - 4;
        $allCharacters = $lowercase . $uppercase . $numbers . $specialChars;
        $password .= substr(str_shuffle($allCharacters), 0, $remainingLength);

        // Shuffle the password to ensure randomness
        return str_shuffle($password);
    }

    public function employeeSeparationIndex(Request $request)
    {
        RolesPermissionController::can(['admindashboard.index']);
        $perPage = $request->get('perpage', 10);

        // Fetch separated employees with pagination
        $employeeSeparations = \App\Models\EmployeeSeparation::with('department', 'designation')->paginate($perPage);

        return view('employees.employee_separation', compact('employeeSeparations', 'perPage'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        RolesPermissionController::can(['employee.view']);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $employee_id)
    {
        RolesPermissionController::can(['employee.edit']);
        $data = [];
        $emp1 = Employee::find($employee_id);

        // Ensure region_ids is always an array
        $emp1->region_ids = is_array($emp1->region_ids) ? $emp1->region_ids : explode(',', $emp1->region_ids ?? '');

        $user = User::find($emp1->user_id);
        $data['user_role'] = $user->roles()->pluck('name')->first();
        $data['employee1'] = $emp1;

        $data['departments'] = Department::get();
        $data['designations'] = Designation::get();
        $data['regions'] = Region::where('p_region_id', 0)->get();
        $data['sub_regions'] = Region::where('p_region_id', '>', 0)->get();
        $data['roles'] = Role::select('name', 'id')->get();
        $data['grades'] = Grade::select('name', 'id')->get();
        $data['employees'] = Employee::with(['user.roles:id,name'])
            ->get()
            ->map(function ($employee) {
                return [
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'role' => $employee->user->roles->pluck('name')->first() ?? 'No role assigned',
                    'region_ids' => is_array($employee->region_ids) ? implode(',', $employee->region_ids) : $employee->region_ids, // Convert region_ids to a comma-separated string if it's an array
                    'sub_region_id' => $employee->sub_region_id,
                ];
            });

        return view('employees.edit', $data);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $employee_id, $channel = "web")
    {
        RolesPermissionController::can(['employee.edit']);
    if (! $employee_id) {
        return redirect()
            ->route('employees.index')
            ->with('error', ['Employee not found.']);
    }

     $userId = (int) $employee_id;

    // Look up the Employee — if missing, show error and bail
    $employee = Employee::find($userId);
    if (! $employee) {
        return redirect()
            ->route('employees.index')
            ->with('error', ['Employee not found.']);
    }

    // Look up the related User — if missing, show error and bail
    $user = User::find($employee->user_id);
    if (! $user) {
        return redirect()
            ->route('employees.index')
            ->with('error', ['Associated user not found.']);
    }
    
        // Prepare and validate input
        $regions_id = array_map('intval', $request->input('region_id', []));
        $validator = Validator::make($request->all(), [
            'employee_id'    => 'required|unique:employees,employee_id,' . $employee->id,
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $user->id,
           
            'report_to'      => 'nullable|exists:employees,employee_id',

        ]);
       if ($validator->fails()) {
        // grab all error messages
        $errors = $validator->errors()->all();
    
        return redirect()->back()
            ->withInput()
              ->with('error', implode(', ', $errors));
    }
    
    
        // Handle profile image if uploaded
        if ($request->hasFile('image')) {
            $filename = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('images/profile'), $filename);
            $user->image     = $filename;
            $employee->image = $filename;
        }
    
        // Update User fields
        $user->name           = $request->name;
        $user->email          = $request->email;
        $user->department_id  = $request->department_id;
        $user->designation_id = $request->designation_id;
        $user->region_ids     = $regions_id;
        // (If you have user_type or status fields you want to allow editing, include them here)
        $user->save();
    
        // Sync roles
        $user->syncRoles($request->roles);
    
        // Update Employee fields
        $employee->employee_id   = $request->employee_id;
        $employee->name          = $request->name;
        $employee->email         = $request->email;
        $employee->secondary_email = $request->secondary_email;
        $employee->department_id   = $request->department_id;
        $employee->designation_id  = $request->designation_id;
        $employee->grade           = $request->grade;
        $employee->mobile          = $request->mobile;
        $employee->region_ids      = $regions_id;
        $employee->sub_region_id   = $request->sub_region_id;
        $employee->report_to       = $request->report_to;
        $employee->status          = $request->status ?? $employee->status;
        $employee->save();
    
        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee and user records have been updated.');
    }



    public function destroy(string $employee_id, $channel = "web")
    {
        // Check user permissions
        RolesPermissionController::can(['employee.delete']);

        // Find the employee
        $employee = Employee::find($employee_id);

        if ($employee) {
            // Find the linked user
            $user = User::find($employee->user_id);

            if ($user) {
                // Set user as inactive
                $user->status = 0;
                $user->inactive_time = Carbon::now();
                $user->save();
            }

            // Set employee as inactive
            $employee->status = 0;
            $employee->inactive_time = Carbon::now();

            // Save changes
            if ($employee->save()) {
                // Send email notification
                \Mail::to($employee->email)->send(new \App\Mail\EmployeeInactiveNotification($employee));

                return redirect()->route('employees.index')->with('success', 'Employee has been inactivated and notified.');
            } else {
                return redirect()->route('employees.index')->with('error', 'Could not inactivate the employee.');
            }
        } else {
            return redirect()->route('employees.index')->with('error', 'Employee not found.');
        }
    }


    public function reactivate(string $employee_id, $channel = "web")
    {
        // Check user permissions
        RolesPermissionController::can(['employee.delete']);

        // Find the employee
        $employee = Employee::find($employee_id);

        if ($employee) {
            // Find the linked user
            $user = User::find($employee->user_id);

            if ($user) {
                // Set user as active
                $user->status = 1;
                $user->inactive_time = null;
                $user->save();
            }

            // Set employee as active
            $employee->status = 1;
            $employee->inactive_time = null;

            // Save changes
            if ($employee->save()) {
                // Send email notification
                \Mail::to($employee->email)->send(new \App\Mail\EmployeeReactivationNotification($employee));

                return redirect()->route('employees.index')->with('success', 'Employee has been reactivated and notified.');
            } else {
                return redirect()->route('employees.index')->with('error', 'Could not reactivate the employee.');
            }
        } else {
            return redirect()->route('employees.index')->with('error', 'Employee not found.');
        }
    }



    public function profileCreate()
    {
        return view('users.profile');
    }
public function export()
    {
        return Excel::download(new EmployeeExport, 'employees_data.xlsx');
    }
    public function profileStore(Request $request)
    {
        return $this->update($request, auth()->user()->id, 'web');
    }

}