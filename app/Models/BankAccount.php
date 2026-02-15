<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'bank_name',
        'account_number',
        'holder_name',
        'currency',
        'initial_balance',
        'current_balance',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(BankMovement::class);
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        return str_repeat('*', max(0, strlen($this->account_number) - 4)) . substr($this->account_number, -4);
    }
}
