<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Employee;
use App\Models\UserSeparation;
use App\Models\EmployeeSeparation;

class CheckInactiveUsersAndEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:inactive-users-employees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check inactive users and employees for more than 45 days and move them to separation tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $thresholdDate = Carbon::now()->subDays(45); // 45 days ago
       
        
        // Process inactive users
        $inactiveUsers = User::where('status', 0)
            ->where("user_type", "!=", "employee")
            ->whereNotNull('inactive_time')
            ->where('inactive_time', '<=', $thresholdDate)
            ->get();

        foreach ($inactiveUsers as $user) {
            // Add user to separation table
            UserSeparation::create([
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'status' => $user->status,
                'image' => $user->image,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'user_type' => $user->user_type,
                'poc_user' => $user->poc_user,
                'gender' => $user->gender,
                'dob' => $user->dob,
                'department_id' => $user->department_id,
                'designation_id' => $user->designation_id,
                'grade' => $user->grade,
                'user_id' => $user->user_id,
                'region_id' => $user->region_id,
                'sub_region_id' => $user->sub_region_id,
                'secondary_email' => $user->secondary_email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'remember_token' => $user->remember_token,
                'inactive_time' => $user->inactive_time,
                'password' => $user->password // Add this line
            ]);

            // Remove user from main table
            $user->delete();
        }

        $this->info("Inactive users processed: " . $inactiveUsers->count());

        // Process inactive employees
        $inactiveEmployees = Employee::where('status', 0)
            ->where('inactive_time', '<=', $thresholdDate)
            ->get();

        foreach ($inactiveEmployees as $employee) {
            // Add employee to separation table
            EmployeeSeparation::create([
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'mobile' => $employee->mobile,
                'status' => $employee->status,
                'image' => $employee->image,
                'email' => $employee->email,
                'gender' => $employee->gender,
                'dob' => $employee->dob,
                'department_id' => $employee->department_id,
                'designation_id' => $employee->designation_id,
                'grade' => $employee->grade,
                'user_id' => $employee->user_id,
                'report_to' => $employee->report_to,
                'region_id' => $employee->region_id,
                'sub_region_id' => $employee->sub_region_id,
                'secondary_email' => $employee->secondary_email,
                'created_at' => $employee->created_at,
                'updated_at' => $employee->updated_at,
                'inactive_time' => $employee->inactive_time,
            ]);

            // Remove employee from main table
            $employee->delete();
        }

        $this->info("Inactive employees processed: " . $inactiveEmployees->count());

        return 0;
    }
}
