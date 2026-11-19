<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSubType extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'name',
        'priority',
    ];
        public function types()
    {
        return $this->belongsToMany(
            TicketType::class,
            'ticket_settings',
            'ticket_sub_type_id',
            'ticket_type_id'
        )->withTimestamps();
    }
}
