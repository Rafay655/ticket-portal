<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'name',
    ];
 
    
         public function subTypes()
    {
        return $this->belongsToMany(
            TicketSubType::class,     // related model
            'ticket_settings',        // pivot table
            'ticket_type_id',         // this model's key on pivot
            'ticket_sub_type_id'      // related model's key on pivot
        )->withTimestamps();
    }
}
