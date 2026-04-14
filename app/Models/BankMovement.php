<?php

namespace App\Models;

use App\Models\Concerns\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankMovement extends Model
{
    use OwnedByAuthenticatedUser;

    protected $fillable = [
        'user_id',
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
        'listado_extra',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'value_date' => 'date',
            'deposit' => 'decimal:2',
            'withdrawal' => 'decimal:2',
            'balance' => 'decimal:2',
            'listado_extra' => 'array',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
