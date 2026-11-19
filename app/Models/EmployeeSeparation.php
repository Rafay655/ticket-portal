<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSeparation extends Model
{
    use HasFactory;

    // Specify the table name (if it differs from the model name in plural form)
    protected $table = 'employee_separation';

    // Allow mass assignment for all columns
    protected $guarded = [];
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    // Define the relationship with the Designation model
    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }
}
