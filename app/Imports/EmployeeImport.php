<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCreatedNotification;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
   
    public function model(array $row)
    {
       
        try {
            // Fetching department ID
            $department = Department::where('name', $row['department'])->first();
            $departmentId = $department ? $department->id : null;

            // Fetching designation ID
            $designation = Designation::where('name', $row['designation'])->first();
            $designationId = $designation ? $designation->id : null;

            // Fetching region ID
            $regionNames = array_map('trim', explode(',', $row['region']));
            $regionIds = Region::whereIn('name', $regionNames)
                ->where('p_region_id', 0)
                ->pluck('id')
                ->toArray();


            // Fetching sub-region ID
            $subRegion = Region::where('name', trim($row['sub_region']))
                ->whereIn('p_region_id', $regionIds)
                ->first();

            $subRegionId = $subRegion ? $subRegion->id : null;


            // Generate strong password
            $plainPassword = $this->generateStrongPassword();

            // Creating a user record
            $userData = [
                'name' => $row['employee_name'],
                'email' => $row['email'],
                'password' => Hash::make($plainPassword), // Hashed password
                'user_type' => 'employee',
                'department_id' => $departmentId,
                'designation_id' => $designationId,
                'region_ids' => $regionIds, // If stored as JSON array in DB
                'secondary_email' => $row['secondary_email'],

                'sub_region_id' => $subRegionId,
                'mobile' => $row['mobile_number'],
                'grade' => $row['grade'],
                'user_id' => 0
            ];

            $user = User::create($userData);

            // Assign role to user
            if (isset($row['role']) && !empty($row['role'])) {
                $user->syncRoles($row['role']);
            }

            // Send email with credentials
            // Mail::to("rafaymalik575763@gmail.com")->send(new UserCreatedNotification($user, $plainPassword));

            // Create an employee record
            $employee = new Employee([
                'employee_id' => $row['employee_id'],
                'name' => $row['employee_name'],
                'designation_id' => $designationId,
                'department_id' => $departmentId,
                'grade' => $row['grade'],
                'region_ids' => $regionIds,
                'sub_region_id' => $subRegionId,
                'report_to' => $row['report_to'],
                'email' => $row['email'],
                'mobile' => $row['mobile_number'],
                'secondary_email' => $row['secondary_email'],
                'user_id' => $user->id,
                'status' => 1
            ]);

            $employee->save();

// Queue the welcome email after saving the employee.
Mail::to($user->email)->queue(
    new UserCreatedNotification($user, $plainPassword)
);

// Already saved; prevent the importer from saving it again.
return null;
        } catch (\Exception $e) {
            // Log the specific error for debugging
            logger()->error('Error importing row: ', [
                'row' => $row,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Generate a strong password with at least 1 uppercase, 1 number, 1 special character.
     *
     * @return string
     */
    private function generateStrongPassword($length = 8)
    {
        if ($length < 8) {
            $length = 8;
        }

        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()-_=+';

        $password = substr(str_shuffle($lowercase), 0, 1) .
            substr(str_shuffle($uppercase), 0, 1) .
            substr(str_shuffle($numbers), 0, 1) .
            substr(str_shuffle($specialChars), 0, 1);

        $remainingLength = $length - 4;
        $allCharacters = $lowercase . $uppercase . $numbers . $specialChars;
        $password .= substr(str_shuffle($allCharacters), 0, $remainingLength);

        return str_shuffle($password);
    }

    /**
     * Define validation rules for the imported data.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|unique:employees,employee_id',
            'employee_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'designation' => 'required|string',

        ];
    }
}
