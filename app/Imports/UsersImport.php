<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Region;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCreatedNotification;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

class UsersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public $failures = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            $department = Department::where('name', $row['department'])->first();
            $departmentId = $department ? $department->id : null;

            $designation = Designation::where('name', $row['designation'])->first();
            $designationId = $designation ? $designation->id : null;

            $region = Region::where('name', $row['region'])->where('p_region_id', 0)->first();
            $regionId = $region ? $region->id : null;

            $subRegion = Region::where('name', $row['sub_region'])
                ->where('p_region_id', $regionId)
                ->first();
            $subRegionId = $subRegion ? $subRegion->id : null;

            // Generate a strong password
            $plainPassword = $this->generateStrongPassword();

            $user = new User([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => Hash::make($plainPassword), // Store the hashed password
                'department_id' => $departmentId,
                'designation_id' => $designationId,
                'mobile' => $row['mobile'],
                'region_id' => $regionId,
                'grade' => $row['grade'],
                'poc_user' => $row['poc_user'],
                'user_id' => $row['user_id'],
                'sub_region_id' => $subRegionId,
                'user_type' => 'user',
                'secondary_email' => $row['secondary_email'],
            ]);

            $user->save();

            // Assign role to user
            if (isset($row['role']) && !empty($row['role'])) {
                $user->syncRoles($row['role']);
            }

            // Send email with credentials
            Mail::to($user->email)->send(new UserCreatedNotification($user, $plainPassword));

            return $user;
        } catch (\Exception $e) {
            // Log the specific error for debugging
            logger()->error('Error importing row: ', [
                'row' => $row,
                'message' => $e->getMessage()
            ]);

            // Add failure information
            $this->failures[] = new Failure(
                data_get($row, 'row_number', null), // If you have a row number
                'import',
                [ $e->getMessage() ],
                $row
            );

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
            '*.name' => 'required',
            '*.email' => 'required|email|unique:users,email',
            '*.department' => 'required|exists:departments,name',
            '*.designation' => 'required|exists:designations,name',
            '*.mobile' => 'required',
            '*.role' => 'required|exists:roles,name',
            '*.region' => 'required|exists:regions,name,p_region_id,0',
            '*.sub_region_id' => 'nullable',
            '*.grade' => 'required',
            '*.poc_user' => ['required', 'boolean'],
        ];
    }

    /**
     * Collect the failures and store them for returning to the front-end.
     *
     * @param Failure ...$failures
     */
    public function onFailure(Failure ...$failures)
    {
        $this->failures = array_merge($this->failures, $failures);
    }
}
