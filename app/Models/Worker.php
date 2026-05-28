<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends Model
{
    protected $fillable = [
        'full_name',
        'nie',
        'bank_account',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(WorkerPayment::class);
    }

    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(WorkerMonthlySummary::class);
    }

    public function projectEntries(): HasMany
    {
        return $this->hasMany(WorkerProjectEntry::class);
    }

    public function touchMonthlySummaryForPeriod(?int $monthlyPeriodId): void
    {
        if (! $monthlyPeriodId) {
            return;
        }

        $summary = WorkerMonthlySummary::firstOrCreate([
            'worker_id' => $this->id,
            'monthly_period_id' => $monthlyPeriodId,
        ]);

        $summary->recalculateFromEntries();
    }
}
