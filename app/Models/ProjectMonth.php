<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMonth extends Model
{
    protected $fillable = [
        'monthly_period_id',
        'client_id',
        'project_id',
        'sheet_code',
        'total_nominal',
        'total_social_security',
        'total_expenses',
        'total_invoiced',
        'estimated_invoice',
        'difference',
        'total_hours',
    ];

    protected $casts = [
        'total_nominal' => 'decimal:2',
        'total_social_security' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_invoiced' => 'decimal:2',
        'estimated_invoice' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_hours' => 'decimal:2',
    ];

    public function monthlyPeriod(): BelongsTo
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectInvoices(): HasMany
    {
        return $this->hasMany(ProjectInvoice::class);
    }

    public function projectExpenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function workerProjectEntries(): HasMany
    {
        return $this->hasMany(WorkerProjectEntry::class);
    }

    public function getComputedDifferenceAttribute(): float
    {
        return (float) $this->estimated_invoice - (float) $this->total_invoiced;
    }

    public function getMarginAttribute(): float
    {
        return (float) $this->total_invoiced - (float) $this->total_expenses - (float) $this->total_nominal - (float) $this->total_social_security;
    }

    public function getMarginPercentAttribute(): float
    {
        if ((float) $this->total_invoiced <= 0) {
            return 0;
        }
        return round(($this->margin / (float) $this->total_invoiced) * 100, 2);
    }
}
