<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentReminder extends Model
{
    protected $fillable = [
        'remindable_type',
        'remindable_id',
        'reminder_date',
        'message',
        'is_sent',
        'is_dismissed',
    ];

    protected function casts(): array
    {
        return [
            'reminder_date' => 'date',
            'is_sent' => 'boolean',
            'is_dismissed' => 'boolean',
        ];
    }

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_sent', false)->where('is_dismissed', false);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->active()->where('reminder_date', '>=', now()->startOfDay());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()->where('reminder_date', '<', now()->startOfDay());
    }
}
