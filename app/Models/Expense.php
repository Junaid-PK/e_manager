<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Expense extends Model
{
    protected $fillable = [
        'company_id',
        'category',
        'description',
        'amount',
        'date',
        'vendor',
        'payment_method',
        'receipt_path',
        'recurring',
        'recurring_frequency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'recurring' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(PaymentReminder::class, 'remindable');
    }
}
