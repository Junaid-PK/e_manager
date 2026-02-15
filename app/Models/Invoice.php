<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'project_id',
        'invoice_number',
        'month',
        'date_issued',
        'date_due',
        'amount',
        'iva_amount',
        'iva_rate',
        'retention_amount',
        'retention_rate',
        'total',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_issued' => 'date',
            'date_due' => 'date',
            'amount' => 'decimal:2',
            'iva_amount' => 'decimal:2',
            'retention_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(PaymentReminder::class, 'remindable');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('date_due', '<', now());
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
