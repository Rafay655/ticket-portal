<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Api\ApiResponseController;
use App\Http\Resources\UserCollection;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Grade;
use App\Models\Region;
use App\Models\MetaLine;
use App\Models\MetaUnit;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCreatedNotification;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $channel = "web")
    {
        RolesPermissionController::can(['user.index']);
        $perPage = \App\Support\ListingFilters::perPage($request);
        $users = \App\Support\ListingFilters::paginate(User::where('user_type', 'user')->with('roles'), $request, ['user_id', 'name', 'email'], ['roles' => ['name']]);
        return view('users.index', compact('users', 'perPage'));
    }



    public function departmentUsers(Request $request)
    {
        $users = User::where('meta_department_id', $request->department_id)->get();
        return $users;
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['user.create']);
        $data = [];
        $data['departments'] = Department::get();
        $data['designations'] = Designation::get();
        $data['regions'] = Region::where('p_region_id', 0)->get();
        $data['sub_regions'] = Region::where('p_region_id', '>', 0)->get();
        $data['roles'] = Role::select('name', 'id')->get();
        $data['grades'] = Grade::select('name', 'id')->get();
        return view('users.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, $channel = "web")
    {
        RolesPermissionController::can(['user.create']);
        $input = $request->all();

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('images/profile'), $imageName);
            $input['image'] = $imageName;
        }

        $plainPassword = $this->generateStrongPassword();
        $input['password'] = Hash::make($plainPassword);
        $input['user_type'] = "user";

        $user = User::create($input);

        if ($request->has('roles') && $request->roles != "") {
            $user->syncRoles($request->roles);
        }

        // Send email with plain password
        Mail::to($user->email)->send(new UserCreatedNotification($user, $plainPassword));

        return redirect()->route('users.index')->with('success', 'User Registered Successfully!');
    }


    public function userSeparationIndex(Request $request)
    {
        RolesPermissionController::can(['admindashboard.index']);
        $perPage = \App\Support\ListingFilters::perPage($request);

        // Fetch separated users with pagination
        $userSeparations = \App\Support\ListingFilters::paginate(\App\Models\UserSeparation::query(), $request, ['id', 'name', 'email', 'separation_date']);

        return view('users.user_separation', compact('userSeparations', 'perPage'));
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


    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        RolesPermissionController::can(['user.view']);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $user_id)
    {
        RolesPermissionController::can(['user.edit']);
        $data = [];
        $data['user'] = User::find($user_id);
        $data['departments'] = Department::get();
        $data['designations'] = Designation::get();
        $data['regions'] = Region::where('p_region_id', 0)->get();
        $data['sub_regions'] = Region::where('p_region_id', '>', 0)->get();
        $data['roles'] = Role::select('name', 'id')->get();
        $data['grades'] = Grade::select('name', 'id')->get();
        return view('users.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $user_id, $channel = "web")
    {
        RolesPermissionController::can(['user.edit']);
        $input = $request->all();
        $user = User::find($user_id);
        if ($request->has('password')) {
            $input['password'] = Hash::make($request->input('password'));
        }
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('images/profile'), $imageName);
            $input['image'] = $imageName;
        }

        if ($request->has('roles') && $request->roles != "") {
            $user->syncRoles($request->roles);
        }

        $user->fill($input);
        $user->save();

        return redirect()->route('users.index')->with('success', 'User has been updated');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $user_id, $channel = "web")
    {
        // Check user permissions
        RolesPermissionController::can(['user.delete']);

        // Find the user
        $user = User::find($user_id);

        if ($user) {
            // Set the user as inactive
            $user->status = 0;
            $user->inactive_time = Carbon::now();

            // Save the changes
            if ($user->save()) {
                // Send email notification
                \Mail::to($user->email)->send(new \App\Mail\UserInactiveNotification($user));

                return redirect()->route('users.index')->with('success', 'User has been inactivated and notified.');
            } else {
                return redirect()->route('users.index')->with('error', 'Could not inactivate the user.');
            }
        } else {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }
    }

    public function reactivate(string $user_id, $channel = "web")
    {
        // Check user permissions
        RolesPermissionController::can(['user.delete']);

        // Find the user
        $user = User::find($user_id);

        if ($user) {
            // Set the user as active
            $user->status = 1; // Assuming 1 means active
            $user->inactive_time = null; // Clear the inactive time

            // Save the changes
            if ($user->save()) {
                // Send email notification
                \Mail::to($user->email)->send(new \App\Mail\UserReactivationNotification($user));

                return redirect()->route('users.index')->with('success', 'User has been reactivated and notified.');
            } else {
                return redirect()->route('users.index')->with('error', 'Could not reactivate the user.');
            }
        } else {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }
    }


    public function profileCreate()
    {
        $data = [];
        $data['user'] = User::find(auth()->user()->id);
        $data['departments'] = Department::get();
        $data['designations'] = Designation::get();
        $data['regions'] = Region::where('p_region_id', 0)->get();
        $data['sub_regions'] = Region::where('p_region_id', '>', 0)->get();
        $data['roles'] = Role::select('name', 'id')->get();
        return view('users.profile', $data);
    }

    public function profileStore(Request $request)
    {
        $user_id = auth()->user()->id;
        $input = $request->all();
        $user = User::find($user_id);
        if ($request->has('password')) {
            $input['password'] = Hash::make($request->input('password'));
        }
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('images/profile'), $imageName);
            $input['image'] = $imageName;
        }

        $user->fill($input);
        $user->save();

        return redirect()->route('users.profileCreate')->with('success', 'User has been updated');
    }

}
