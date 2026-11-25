<?php

namespace App\Http\Controllers;

use App\Models\IncidentAssign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
class RolesPermissionController extends Controller
{

    public function index(Request $request)
    {
        self::can(['role.index']);

        // Fetch the 'perpage' parameter or use the default value of 10
        $perPage = \App\Support\ListingFilters::perPage($request);
        $query = Role::query();
        if (!auth()->user()->hasRole('superAdmin', 'web')) $query->whereNotIn('name', ['superAdmin', 'admin']);
        $roles = \App\Support\ListingFilters::paginate($query, $request, ['name']);

        // Check if the request is AJAX (for DataTables integration)
        if ($request->ajax()) {
            // Paginate the roles based on the perPage value


            $data = [];
            $i = ($roles->firstItem() ?? 1) - 1; // Calculate serial numbers

            foreach ($roles as $role) {
                $i++;
                $data[] = [
                    'sno' => $i,
                    'name' => $role->name,
                    'action' => view('roles.partials.action-buttons', ['role' => $role])->render() // Render action buttons
                ];
            }

            return response()->json([
                'data' => $data,
                'pagination' => $roles->links('pagination::bootstrap-4')->render() // Render pagination links
            ]);
        }

        // Render the view for normal requests

        return view('roles.index', compact('roles', 'perPage'));
    }



    public function show($role_id)
    {
        self::can(['role.view']);

        $role = Role::where('id', $role_id)->first();
        $permissions = [];
        if ($role) {
            $permissions = $role->permissions->pluck('name');
        }
        $modules = $this->getSubModules();

        return view('roles.show', compact('role', 'permissions', 'modules'));
    }
    public function create()
    {
        self::can(['role.create']);
        $modules = $this->getSubModules();
        return view('roles.create', compact('modules'));
    }
    public function store(Request $request)
    {
        self::canAny(['role.create', 'role.edit']);

        // Add validation for unique role name
        $validator = Validator::make($request->all(), [
            'role_name' => [
                'string',
                'required',
                Rule::unique('roles', 'name')->ignore($request->role_id) // Ignore current role when updating
            ],
            'permissions' => ['required'],
            'permissions.*' => ['string', 'required'],
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return ['error', $error];
        }

        if ($request->has('role_id')) {
            self::can([ 'role.edit']);
            $message = "Role has been updated";
            $role = Role::where('id', $request->role_id)->first();
            $role->name = $request->role_name;
            $role->save();
        } else {
            self::can([ 'role.create']);
            $message = "Role has been created";
            $role = Role::create(['name' => $request->role_name]);
        }
        foreach ($request->permissions as $permission) {
            // Check if the permission already exists in the database
            $exists = Permission::where('name', $permission)->exists();

            // If it doesn't exist, create it
            if (!$exists) {
                Permission::create(['name' => $permission]);
            }
        }

        $role->syncPermissions($request->permissions);

        return ['success', $message, $request->redirect];
    }


    public static function can($permission1)
    {
        if (!auth()->check()) {
            return abort(401, 'You are not authorized to perform this action');
        }

        if ($permission1 != null) {

            $permission = auth()->user()->can($permission1);
            if (!$permission) {
                return abort(401, 'You are not authorized to perform this action');
            }
        } else{
            return abort(401, 'You are not authorized to perform this action');
        }
    }
    public static function canAny($permission1)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if($permission1){

            $permission = auth()->user()->canany($permission1);
            if (!$permission) {
                return abort(401, 'You are not authorized to perform this action');
            }
        }else{
            return abort(401, 'You are not authorized to perform this action');
        }
    }

    // public static function canEditIncident($incident, $incident_name)
    // {
    //     // if user is admin then he can edit any incident
    //     if (auth()->user()->can(["{$incident_name}.index", "{$incident_name}.edit", "{$incident_name}.delete"])) {
    //         return true;
    //     }
    //     // if user is not admin then we will check if he is allowed to edit or not the specific incident
    //     $form_name = $incident->getTable();
    //     $incident_id = $incident->id;
    //     $incidentAssigned = IncidentAssign::where('incident_id', $incident_id)
    //         ->where('form_name', $form_name)
    //         ->get();

    //     // if user is allowed to edit and the status of that incdient is not completed
    //     // below status_code < 3 is in progress, pending, etc statuses
    //     // after 3 is completed,closed etc
    //     if (@$incidentAssigned->last()->assign_to == auth()->user()->id && $incident->incident_status->status_code < 3) {
    //         return true;
    //     } else {
    //         return abort(401, 'You are not authorized to perform this actionss');
    //     }

    // }
    // public static function canViewIncident($incident, $incident_name)
    // {
    //     // if user is admin then he can viw any incident
    //     if (auth()->user()->can(["{$incident_name}.index", "{$incident_name}.edit", "{$incident_name}.delete"])) {
    //         return true;
    //     }

    //     // if user is not admin then we will check if he is allowed to view or not the specific incident
    //     $form_name = $incident->getTable();
    //     $incident_id = $incident->id;
    //     $incidentAssigned = IncidentAssign::where('incident_id', $incident_id)
    //         ->where('form_name', $form_name)
    //         ->get();

    //     // incidnet assigner can view
    //     // incident assign to can view
    //     // incident initator can view
    //     if ($incidentAssigned->where('assign_to', auth()->user()->id)->count() > 0 or $incidentAssigned->where('assign_by', auth()->user()->id)->count() > 0 or $incident->initiated_by === auth()->user()->id) {
    //         return true;
    //     } else {
    //         return abort(401, 'You are not authorized to perform this action');
    //     }

    // }

    public function destroy($role_id)
    {
        self::can(['role.delete']);

        $role = Role::find($role_id);

        if (!$role) {
            return response()->json(['Role not found'], 404);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['Cannot delete role as users are attached to it'], 400);
        }

        $role->delete();

        return response()->json(['deleted', 'Role deleted successfully'], 200);
    }


    public function fetchPermissions()
    {
        return auth()->user()->getAllPermissions()->pluck('name');
    }

    public function getSubModules()
    {
        return collect([
            ['name' => 'User', 'slug' => 'user'],
            ['name' => 'Employee', 'slug' => 'employee'],
            ['name' => 'Roles', 'slug' => 'role'],
            ['name' => 'Ticket', 'slug' => 'ticket'],
            ['name' => 'Report', 'slug' => 'report'],
            ['name' => 'Business Hours', 'slug' => 'businesshours'],
            ['name' => 'SLA Policies', 'slug' => 'slapolicies'],
            ['name' => 'Ticket Type', 'slug' => 'ticketType'],
            ['name' => 'Ticket Category', 'slug' => 'ticketCategory'],
            ['name' => 'Ticket Sub-Category', 'slug' => 'ticketSubType'],
            ['name' => 'Ticket Settings', 'slug' => 'ticketSettings'],
            ['name' => 'Designation', 'slug' => 'designation'],
            ['name' => 'Department', 'slug' => 'department'],
            ['name' => 'Region', 'slug' => 'region'],
            ['name' => 'Grade', 'slug' => 'grade'],
            ['name' => 'Admin Dashboard', 'slug' => 'admindashboard'],
        ]);
    }
}
