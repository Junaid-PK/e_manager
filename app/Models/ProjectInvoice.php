<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInvoice extends Model
{
    protected $fillable = [
        'project_month_id',
        'invoice_no',
        'invoice_date',
        'estimated_amount',
        'actual_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'estimated_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
    ];

    public function projectMonth(): BelongsTo
    {
        return $this->belongsTo(ProjectMonth::class);
    }

    public function getDifferenceAttribute(): float
    {
        return (float) $this->actual_amount - (float) $this->estimated_amount;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'paid' => 'emerald',
            'partial' => 'amber',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => __('app.draft'),
            'sent' => __('app.sent'),
            'paid' => __('app.paid'),
            'partial' => __('app.partial'),
            'cancelled' => __('app.cancelled'),
            default => ucfirst($this->status),
        };
    }
}
