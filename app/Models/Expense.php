<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Expense extends Model
{
    public const CATEGORIES = [
        'Salario Bruto',
        'Seguridad Social',
        'Autonomo',
        'Compra 1T',
        'Compra 2T',
        'Compra 3T',
        'Compra 4T',
        'Modelo 303 1T',
        'Modelo 303 2T',
        'Modelo 303 3T',
        'Modelo 303 4T',
        'Gasto Confirming',
        'Bank Commission',
        'Seguro Bank',
        'Seguro de Empresa',
        'Seguridad IT',
        'Juzgado',
        'Restaurante',
        'Dieta',
    ];

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
