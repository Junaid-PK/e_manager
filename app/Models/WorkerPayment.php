<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerPayment extends Model
{
    protected $fillable = [
        'worker_id',
        'monthly_period_id',
        'project_month_id',
        'payment_date',
        'payment_type',
        'amount',
        'reference',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function monthlyPeriod(): BelongsTo
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }

    public function projectMonth(): BelongsTo
    {
        return $this->belongsTo(ProjectMonth::class);
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match ($this->payment_type) {
            'bank' => __('app.bank'),
            'cash' => __('app.cash'),
            'advance' => __('app.advance'),
            'ticket' => __('app.ticket'),
            'adjustment' => __('app.adjustment'),
            default => $this->payment_type,
        };
    }

    public function getPaymentTypeColorAttribute(): string
    {
        return match ($this->payment_type) {
            'bank' => 'text-blue-600 dark:text-blue-400',
            'cash' => 'text-emerald-600 dark:text-emerald-400',
            'advance' => 'text-purple-600 dark:text-purple-400',
            'ticket' => 'text-yellow-600 dark:text-yellow-400',
            'adjustment' => 'text-orange-600 dark:text-orange-400',
            default => 'text-gray-600 dark:text-gray-400',
        };
    }
}
