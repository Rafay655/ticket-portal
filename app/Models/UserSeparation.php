<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSeparation extends Model
{
    use HasFactory;

    // Specify the table name (if it differs from the model name in plural form)
    protected $table = 'user_separation';

    // Allow mass assignment for all columns
    protected $guarded = [];
}
