<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExpense extends Model
{
    protected $fillable = [
        'project_month_id',
        'expense_date',
        'category',
        'description',
        'amount',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function projectMonth(): BelongsTo
    {
        return $this->belongsTo(ProjectMonth::class);
    }
}
