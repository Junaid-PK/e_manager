<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCif extends Model
{
    protected $fillable = [
        'code',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function providers(): HasMany
    {
        return $this->hasMany(ExpenseProvider::class, 'expense_cif_id');
    }
}
