<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function categories()
    {
        return $this->hasMany(TicketCategory::class, 'ticket_type_id');
    }

    public function subTypes()
    {
        // Retain the existing method/API name, now following the three-level hierarchy.
        return $this->hasManyThrough(TicketSubType::class, TicketCategory::class, 'ticket_type_id', 'ticket_category_id');
    }
}
