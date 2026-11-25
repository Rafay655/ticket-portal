<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
    protected $fillable = ['name', 'code', 'ticket_type_id'];

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }

    public function subCategories()
    {
        return $this->hasMany(TicketSubType::class, 'ticket_category_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'ticket_category_id');
    }
}
