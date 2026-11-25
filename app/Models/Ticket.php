<?php

namespace App\Models;
use App\Models\Region;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{

    protected $fillable = [
        'tickettype_id',
        'ticket_category_id',
        'ticketsubtype_id',
        'description',
        'ticket_attachment',
        'requested_by',
        'assigned_to',
        'status',
        'current_level',
        'completed_at',
        'escalation_time_start'

    ];

    protected $appends = ['ticket_category_name'];

    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function getTicketCategoryNameAttribute(): ?string
    {
        if ($this->ticketCategory) return $this->ticketCategory->name;
        // Old tickets retain their original IDs. Show a mapping only when its type matches.
        $subCategory = $this->relations['ticketsubtype'] ?? $this->relations['ticketSubType'] ?? $this->ticketsubtype;
        $category = $subCategory?->category;
        return $category && (int) $category->ticket_type_id === (int) $this->tickettype_id ? $category->name : null;
    }

    public static function getRouteName()
    {
        return 'employees.show'; //show route name
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tickettype()
    {
        return $this->belongsTo(TicketType::class, 'tickettype_id');
    }

    public function ticketsubtype()
    {
        return $this->belongsTo(TicketSubType::class, 'ticketsubtype_id');
    }

    public function requestedby()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignto()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'ticket_id');
    }

    public function feedback()
    {
        return $this->hasMany(TicketFeedback::class, 'ticket_id');
    }
    public function shouldEscalate(): bool
    {
        return app(\App\Services\TicketResponseSla::class)->shouldEscalate($this);
    }

    public function getMinutesSinceCreatedDuringBusinessHours($createdAt): int
    {
        $calendar = app(\App\Services\TicketResponseSla::class)->calendar();
        return $calendar ? app(\App\Services\BusinessHoursClock::class)->elapsedMinutes($calendar, $createdAt) : 0;
    }

    public function calculateDueDate()
    {
        return app(\App\Services\TicketResponseSla::class)->dueAt($this);
    }

    public function calculateDueDateDuringBusinessHours($createdAt, $requiredMinutes)
    {
        $calendar = app(\App\Services\TicketResponseSla::class)->calendar();
        return $calendar ? app(\App\Services\BusinessHoursClock::class)->addMinutes($calendar, $createdAt, (int) $requiredMinutes) : null;
    }
}
