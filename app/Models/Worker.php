<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends Model
{
    protected $fillable = [
        'full_name',
        'role',
        'nie',
        'bank_account',
        'rate',
        'import_status',
        'first_imported_at',
        'last_imported_at',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'first_imported_at' => 'datetime',
        'last_imported_at' => 'datetime',
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

    public function importEntries(): HasMany
    {
        return $this->hasMany(WorkerImportEntry::class);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('import_status', $status);
    }

    public function scopeNewlyImported(Builder $query): Builder
    {
        return $query->where('import_status', 'new');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('import_status', 'active');
    }

    public function scopeRemoved(Builder $query): Builder
    {
        return $query->where('import_status', 'removed');
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
