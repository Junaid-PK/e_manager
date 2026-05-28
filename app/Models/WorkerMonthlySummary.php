<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerMonthlySummary extends Model
{
    protected $fillable = [
        'monthly_period_id',
        'worker_id',
        'total_amount',
        'paid_amount',
        'total_hours',
        'payroll_amount',
        'advance_amount',
        'credit_amount',
        'ticket_amount',
        'difference',
        'final_difference',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'payroll_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'ticket_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'final_difference' => 'decimal:2',
    ];

    public function monthlyPeriod(): BelongsTo
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Calculate the net amount after all deductions.
     * This is what the worker actually receives.
     */
    public function getNetAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->payroll_amount - (float) $this->advance_amount - (float) $this->credit_amount - (float) $this->ticket_amount;
    }

    /**
     * Calculate the remaining balance (what worker is still owed).
     */
    public function getRemainingBalanceAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    public function recalculateFromEntries(): void
    {
        $entries = WorkerProjectEntry::where('worker_id', $this->worker_id)
            ->whereHas('projectMonth', fn ($q) =>
                $q->where('monthly_period_id', $this->monthly_period_id))
            ->get();

        $this->total_amount = $entries->sum('total_amount');
        $this->total_hours = $entries->sum('hours');
        $this->payroll_amount = $entries->sum('social_security');
        $this->save();
    }

    /**
     * Auto-calculate difference and final_difference before saving.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($summary) {
            $summary->difference = (float) $summary->total_amount - (float) $summary->paid_amount;
            $summary->final_difference = (float) $summary->difference
                - (float) $summary->payroll_amount
                - (float) $summary->advance_amount
                - (float) $summary->credit_amount
                - (float) $summary->ticket_amount;
        });
    }
}
