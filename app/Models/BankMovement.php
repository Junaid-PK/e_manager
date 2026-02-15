<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankMovement extends Model
{
    protected $fillable = [
        'bank_account_id',
        'date',
        'value_date',
        'type',
        'concept',
        'beneficiary',
        'reference',
        'deposit',
        'withdrawal',
        'balance',
        'category',
        'notes',
        'import_source',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'value_date' => 'date',
            'deposit' => 'decimal:2',
            'withdrawal' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
