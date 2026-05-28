<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerProjectEntry extends Model
{
    protected $fillable = [
        'project_month_id',
        'worker_id',
        'special_note',
        'social_security',
        'hours',
        'days',
        'rate',
        'total_amount',
        'paid_amount',
    ];

    protected $casts = [
        'social_security' => 'decimal:2',
        'hours' => 'decimal:2',
        'days' => 'decimal:2',
        'rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($entry) {
            $entry->total_amount = (float) $entry->social_security
                + ((float) $entry->hours * (float) $entry->rate);
        });

        static::saved(function ($entry) {
            $entry->projectMonth?->recalculateTotals();
            $entry->worker?->touchMonthlySummaryForPeriod($entry->projectMonth?->monthly_period_id);
        });

        static::deleted(function ($entry) {
            $entry->projectMonth?->recalculateTotals();
            $entry->worker?->touchMonthlySummaryForPeriod($entry->projectMonth?->monthly_period_id);
        });
    }

    public function projectMonth(): BelongsTo
    {
        return $this->belongsTo(ProjectMonth::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->remaining_amount <= 0.01;
    }
}
