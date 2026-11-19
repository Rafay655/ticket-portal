<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Support\Facades\File;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MeController extends Controller
{
    // Change this if your column is named differently:
    private const EMPLOYEE_DOB_COLUMN = 'dob'; // e.g. 'dob' or 'date_of_birth'
    private const EMPLOYEE_IMAGE_COLUMN = 'image';       // matches your existing code
    
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found.'], 404);
        }

        // Validate (all fields optional; only validate what is present)
        $validator = Validator::make($request->all(), [
            'name'             => ['sometimes', 'string', 'max:255'],
            'mobile'           => ['sometimes', 'string', 'max:20'],
            'email'            => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'secondary_email'  => ['sometimes', 'nullable', 'email', 'different:email'],
            'date_of_birth'    => ['sometimes', 'date', 'before:today'],
            'gender'           => ['sometimes', 'in:male,female,other,prefer_not_to_say'],
            'picture'          => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'secondary_email.different' => 'Secondary email must be different from primary email.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Ensure at least one updatable field is present
        $allowedKeys = ['name', 'mobile', 'email', 'secondary_email', 'date_of_birth', 'gender', 'picture'];
        if (!array_intersect($allowedKeys, array_keys($request->all()))) {
            return response()->json([
                'success' => false,
                'message' => 'No updatable fields provided.',
            ], 422);
        }

        $data = $validator->validated();

        // Do everything atomically
        return DB::transaction(function () use ($request, $user, $employee, $data) {
            $didChangeEmail = false;

            // --- Update User fields (name, email) ---
            if (array_key_exists('name', $data)) {
                $user->name = $data['name'];
            }
            if (array_key_exists('email', $data)) {
                $didChangeEmail = strcasecmp($user->email, $data['email']) !== 0;
                $user->email = strtolower($data['email']);
                // If you use email verification, uncomment next line:
                // $user->email_verified_at = null;
            }
            $user->save();

            // --- Update Employee fields ---
            if (array_key_exists('name', $data)) {
                $employee->name = $data['name']; // keep in sync if you mirror name on employees table
            }
            if (array_key_exists('email', $data)) {
                $employee->email = $user->email; // keep in sync if you mirror email on employees table
            }
            if (array_key_exists('mobile', $data)) {
                $employee->mobile = $data['mobile'];
            }
            if (array_key_exists('secondary_email', $data)) {
                $employee->secondary_email = $data['secondary_email'];
            }
            if (array_key_exists('gender', $data)) {
                $employee->gender = $data['gender'];
            }
            if (array_key_exists('date_of_birth', $data)) {
                // Adjust column name here if needed
                $dobColumn = self::EMPLOYEE_DOB_COLUMN;
                $employee->{$dobColumn} = $data['date_of_birth'];
            }

            // --- Handle picture upload ---
            $pictureUrl = null;
            if ($request->hasFile('picture')) {
                // Ensure you have run: php artisan storage:link
                $path = $request->file('picture')->store('profile', 'public');

                // Optionally delete old picture if stored
                $old = $employee->{self::EMPLOYEE_IMAGE_COLUMN} ?? null;
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }

                // Save relative path in DB, expose URL in response
                $employee->{self::EMPLOYEE_IMAGE_COLUMN} = $path;
                $pictureUrl = Storage::url($path);
            }

            $employee->save();

            // Optional: if email changed, revoke other tokens (security)
            if ($didChangeEmail) {
                $currentTokenId = $user->currentAccessToken()?->id;
                $user->tokens()->when($currentTokenId, fn($q) => $q->where('id', '!=', $currentTokenId))->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data'    => [
                    'user' => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                    ],
                    'employee' => [
                        'id'               => $employee->id,
                        'name'             => $employee->name ?? null,
                        'email'            => $employee->email ?? null,
                        'mobile'           => $employee->mobile ?? null,
                        'secondary_email'  => $employee->secondary_email ?? null,
                        'gender'           => $employee->gender ?? null,
                        'date_of_birth'    => $employee->{self::EMPLOYEE_DOB_COLUMN} ?? null,
                        'picture_path'     => $employee->{self::EMPLOYEE_IMAGE_COLUMN} ?? null,
                        'picture_url'      => $pictureUrl ?? ($employee->{self::EMPLOYEE_IMAGE_COLUMN} ? Storage::url($employee->{self::EMPLOYEE_IMAGE_COLUMN}) : null),
                    ],
                ],
            ], 200);
        });
    }

    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $employee = Employee::with([
    'user.department:id,name',
    'user.designation:id,name',
])->where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found.'], 404);
        }

        // Figure out a usable picture URL (supports both /storage and /public/images/profile patterns)
        $imgCol     = property_exists($employee, 'image') ? 'image' : 'picture';
        $pictureVal = $employee->{$imgCol} ?? null;
        $pictureUrl = null;

        if ($pictureVal) {
            // storage/app/public/...
            if (Storage::disk('public')->exists($pictureVal)) {
                $pictureUrl = Storage::url($pictureVal);
            }
            // legacy: public/images/profile/<filename>
            elseif (is_string($pictureVal) && file_exists(public_path('images/profile/' . $pictureVal))) {
                $pictureUrl = asset('images/profile/' . $pictureVal);
            }
            // already a full URL?
            elseif (str_starts_with($pictureVal, 'http://') || str_starts_with($pictureVal, 'https://')) {
                $pictureUrl = $pictureVal;
            }
        }
          $imageUrl = $user->image
                ? asset('images/profile/'.$user->image)   // same path as your upload
                : null;
        // If your DOB column is named 'dob', change this to 'dob'
        $dob = $employee->date_of_birth ?? ($employee->dob ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'email'             => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at'        => $user->created_at,
                    'updated_at'        => $user->updated_at,
                ],
                'employee' => [
                    'id'               => $employee->id,
                    'employee_id'      => $employee->employee_id ?? null,
                    'name'             => $employee->name ?? null,
                    'email'            => $employee->email ?? null,
                    'secondary_email'  => $employee->secondary_email ?? null,
                    'mobile'           => $employee->mobile ?? null,
                    'gender'           => $employee->gender ?? null,
                    'date_of_birth'    => $dob,
                    'department'    => $employee->department->name ?? null,
                    'designation'   => $employee->designation->name ?? null,
                    'picture_path'     => $pictureVal,
                    'picture_url'      => $imageUrl,
                    'created_at'       => $employee->created_at,
                    'updated_at'       => $employee->updated_at,
                ],
            ],
        ], 200);
    }
    
    public function updateImage(Request $request)
    {
    $request->validate([
        'image' => ['required','image','mimes:jpg,jpeg,png,webp','max:2048'],
    ]);

    $user = $request->user();

    // Ensure target directory exists
    $dest = public_path('images/profile');
    if (! File::exists($dest)) {
        File::makeDirectory($dest, 0775, true);
    }

    // Remove previous file if present
    if (!empty($user->image) && File::exists($dest.'/'.$user->image)) {
        File::delete($dest.'/'.$user->image);
    }

    // Save new file with time().ext (same as your store() method)
    $ext       = $request->file('image')->getClientOriginalExtension();
    $imageName = time().'.'.$ext; // or time().'-'.$user->id.'.'.$ext for extra uniqueness
    $request->file('image')->move($dest, $imageName);

    // Persist filename in DB
    $user->image = $imageName;
    $user->save();

    // Return absolute URL for frontend
    return response()->json([
        'message'   => 'Profile image updated',
        'image'     => $imageName,
        'image_url' => asset('images/profile/'.$imageName),
    ]);
}

   public function myTeam(Request $request)
{
    $user = $request->user();
    $me = \App\Models\Employee::where('user_id', $user->id)->first();

    if (!$me) {
        return response()->json(['success' => false, 'message' => 'Employee record not found for user.'], 404);
    }

    $q           = trim((string) $request->query('q', ''));
    $statusParam = $request->query('status'); // active|inactive|null
    $perPage     = (int) $request->query('per_page', 20);
    $includeSelf = filter_var($request->query('include_self', false), FILTER_VALIDATE_BOOLEAN);

    $query = \App\Models\Employee::query()
        ->leftJoin('users',        'users.id',        '=', 'employees.user_id')
        ->leftJoin('departments',  'departments.id',  '=', 'employees.department_id')
        ->leftJoin('designations', 'designations.id', '=', 'employees.designation_id')
        ->select([
            'employees.id',
            'employees.employee_id',
            'employees.user_id', // needed for ticket stats
            'employees.name',
            'employees.email',
            'employees.mobile',
            'employees.gender',
            'employees.status',
            'employees.image',
            'users.image as user_image',
            'employees.department_id',
            'employees.designation_id',
            'departments.name  as department_name',
            'designations.name as designation_name',
        ])
        ->where(function ($w) use ($me, $includeSelf) {
            $w->where('employees.report_to', $me->employee_id);
            if ($includeSelf) {
                $w->orWhere('employees.id', $me->id);
            }
        });

    if ($q !== '') {
        $query->where(function ($qq) use ($q) {
            $qq->where('employees.name', 'like', "%{$q}%")
               ->orWhere('employees.employee_id', 'like', "%{$q}%")
               ->orWhere('employees.email', 'like', "%{$q}%")
               ->orWhere('employees.mobile', 'like', "%{$q}%");
        });
    }

    if ($statusParam === 'active') {
        $query->where('employees.status', 1);
    } elseif ($statusParam === 'inactive') {
        $query->where('employees.status', 0);
    }

    $paginator = $query
        ->orderBy('employees.status', 'desc')
        ->orderBy('employees.name', 'asc')
        ->paginate(max($perPage, 1));

    // ===== Bulk stats for everyone on this page =====
    $itemsRaw      = collect($paginator->items());
    $userIdsOnPage = $itemsRaw->pluck('user_id')->filter()->unique()->values()->all();

    // NEW: role (Manager/Employee) via direct report count
    $managerEmpIds = $itemsRaw->pluck('employee_id')->filter()->unique()->values()->all();
    $reporteeCounts = collect();
    if (!empty($managerEmpIds)) {
        $reporteeCounts = \DB::table('employees')
            ->select('report_to', \DB::raw('COUNT(*) as total'))
            ->whereIn('report_to', $managerEmpIds)
            ->groupBy('report_to')
            ->pluck('total', 'report_to'); // key = manager employee_id
    }

    // Helpers
    $emptyBuckets = fn() => ['Open'=>0, 'in_progress'=>0, 'Closed'=>0, 'Feedback'=>0];

    $assignedCounts   = collect(); // totals where assigned_to = uid
    $createdCounts    = collect(); // totals where requested_by = uid
    $assignedBuckets  = [];        // uid => status buckets
    $createdBuckets   = [];        // uid => status buckets
    $happiness        = collect(); // uid => avg rate

    if (!empty($userIdsOnPage)) {
        // Totals (assigned)
        $assignedCounts = \DB::table('tickets')
            ->select('assigned_to as uid', \DB::raw('COUNT(*) as total'))
            ->whereIn('assigned_to', $userIdsOnPage)
            ->groupBy('assigned_to')
            ->pluck('total', 'uid');

        // Totals (created)
        $createdCounts = \DB::table('tickets')
            ->select('requested_by as uid', \DB::raw('COUNT(*) as total'))
            ->whereIn('requested_by', $userIdsOnPage)
            ->groupBy('requested_by')
            ->pluck('total', 'uid');

        // Buckets (assigned)
        $rowsA = \DB::table('tickets')
            ->select('assigned_to as uid', 'status', \DB::raw('COUNT(*) as total'))
            ->whereIn('assigned_to', $userIdsOnPage)
            ->groupBy('assigned_to', 'status')
            ->get();

        foreach ($userIdsOnPage as $uid) $assignedBuckets[$uid] = $emptyBuckets();
        foreach ($rowsA as $r) {
            $uid = (int) $r->uid;
            if (isset($assignedBuckets[$uid][$r->status])) {
                $assignedBuckets[$uid][$r->status] = (int) $r->total;
            }
        }

        // Buckets (created)
        $rowsC = \DB::table('tickets')
            ->select('requested_by as uid', 'status', \DB::raw('COUNT(*) as total'))
            ->whereIn('requested_by', $userIdsOnPage)
            ->groupBy('requested_by', 'status')
            ->get();

        foreach ($userIdsOnPage as $uid) $createdBuckets[$uid] = $emptyBuckets();
        foreach ($rowsC as $r) {
            $uid = (int) $r->uid;
            if (isset($createdBuckets[$uid][$r->status])) {
                $createdBuckets[$uid][$r->status] = (int) $r->total;
            }
        }

        // Happiness rating (avg feedback rate) for assigned tickets
        $happiness = \DB::table('tickets')
            ->join('ticket_feedback', 'tickets.id', '=', 'ticket_feedback.ticket_id')
            ->select('tickets.assigned_to as uid', \DB::raw('AVG(ticket_feedback.rate) as avg_rate'))
            ->whereIn('tickets.assigned_to', $userIdsOnPage)
            ->groupBy('tickets.assigned_to')
            ->pluck('avg_rate', 'uid');
    }

    // ===== Map to response (picture URL logic unchanged) =====
    $items = $itemsRaw->map(function ($row) use ($assignedCounts, $createdCounts, $assignedBuckets, $createdBuckets, $happiness, $reporteeCounts) {
        $filenameUser = $row->user_image ?? null;
        $filenameEmp  = $row->image ?? null;

        $pictureUrl = null;
        if ($filenameUser) {
            $pictureUrl = preg_match('#^https?://#i', $filenameUser)
                ? $filenameUser
                : asset('images/profile/'.$filenameUser);
        } elseif ($filenameEmp) {
            if (preg_match('#^https?://#i', $filenameEmp)) {
                $pictureUrl = $filenameEmp;
            } elseif (is_string($filenameEmp) && file_exists(public_path('images/profile/'.$filenameEmp))) {
                $pictureUrl = asset('images/profile/'.$filenameEmp);
            } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($filenameEmp)) {
                $pictureUrl = \Illuminate\Support\Facades\Storage::url($filenameEmp);
            } else {
                $pictureUrl = asset('images/profile/'.$filenameEmp);
            }
        }

        $uid        = (int) ($row->user_id ?? 0);
        $myEmpId    = $row->employee_id;
        $isManager  = (int) ($reporteeCounts[$myEmpId] ?? 0) > 0;   // NEW: role
        $role       = $isManager ? 'Manager' : 'Employee';          // NEW: role

        return [
            'id'               => (int) $row->id,
            'employee_id'      => $row->employee_id,
            'name'             => $row->name,
            'email'            => $row->email,
            'mobile'           => $row->mobile,
            'gender'           => $row->gender,
            'status'           => (int) $row->status,
            'status_label'     => ((int) $row->status === 1) ? 'Active' : 'Inactive',
            'designation'      => $row->designation_name ?? null,
            'department'       => $row->department_name  ?? null,
            'picture_url'      => $pictureUrl,

            // Totals
            'assigned_count'   => (int) ($assignedCounts[$uid] ?? 0),
            'created_count'    => (int) ($createdCounts[$uid]  ?? 0),

            // Buckets
            'assigned_status'  => $assignedBuckets[$uid] ?? ['Open'=>0,'in_progress'=>0,'Closed'=>0,'Feedback'=>0],
            'created_status'   => $createdBuckets[$uid]  ?? ['Open'=>0,'in_progress'=>0,'Closed'=>0,'Feedback'=>0],

            // Rating
            'happiness_rating' => isset($happiness[$uid]) ? round((float)$happiness[$uid], 2) : 0.0,

            // NEW: single extra field
            'role'             => $role,  // "Manager" | "Employee"
        ];
    });

    return response()->json([
        'success' => true,
        'data' => [
            'items'      => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ],
    ]);
}


    
    
    
}
