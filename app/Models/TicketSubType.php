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
        'ticket_category_id',
    ];
    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'ticketsubtype_id');
    }

    // Legacy settings links remain available for historical records.
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
