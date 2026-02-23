<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CreditLine extends Model
{
    public const ENTITY_TYPES = ['bank', 'company'];
    public const STATUSES = ['active', 'paid_off', 'defaulted'];

    protected $fillable = [
        'entity_name',
        'entity_type',
        'year',
        'total_amount',
        'amount_paid',
        'amount_remaining',
        'interest_rate',
        'start_date',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_remaining' => 'decimal:2',
            'interest_rate' => 'decimal:2',
        ];
    }

    public function computeRemaining(): void
    {
        $this->amount_remaining = round((float) $this->total_amount - (float) $this->amount_paid, 2);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
