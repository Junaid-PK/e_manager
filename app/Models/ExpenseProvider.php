<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseProvider extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'expense_cif_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function cif(): BelongsTo
    {
        return $this->belongsTo(ExpenseCif::class, 'expense_cif_id');
    }
}
