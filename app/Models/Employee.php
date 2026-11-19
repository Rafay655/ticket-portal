<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;   // ← ADD THIS
use App\Models\Region; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{

    protected $fillable = [
        'user_id',
        'name',
        'department_id',
        'designation_id',
        'mobile',
        'grade',
        'employee_id',
      'region_ids',
        'sub_region_id',
        'report_to',
        'email',
        'user_type',
        'secondary_email',
        'image',
        'password',
        "status"
    ];
    protected $casts = [
        // … other casts …,
        'region_ids' => 'array',
      ];
     
    protected $appends = [
    'region_names',   // <-- auto-include in JSON
    ];
    
    // (optional) hide the raw ids if you don’t want them
    protected $hidden = [
        'region_ids',
    ];
     
    public static function getRouteName()
    {
        return 'employees.show'; //show route name
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
       public function designation() { return $this->belongsTo(Designation::class, 'designation_id'); }
    public function department()  { return $this->belongsTo(Department::class,  'department_id'); }
    public function region()      { return $this->belongsTo(Region::class,      'region_id'); }
     public function subregion()
    {
        return $this->belongsTo(Region::class, 'sub_region_id');
    }
 
     protected function regionNames(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ids = array_filter((array) $this->region_ids);
                if (empty($ids)) return '';
                return Region::whereIn('id', $ids)
                    ->orderBy('name')
                    ->pluck('name')
                    ->implode(', ');
            }
        );
    }


}
