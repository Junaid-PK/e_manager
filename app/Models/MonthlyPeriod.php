<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyPeriod extends Model
{
    protected $fillable = [
        'period_code',
        'year',
        'month',
        'label',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function workerSummaries(): HasMany
    {
        return $this->hasMany(WorkerMonthlySummary::class);
    }

    public function workerPayments(): HasMany
    {
        return $this->hasMany(WorkerPayment::class);
    }

    public function projectMonths(): HasMany
    {
        return $this->hasMany(ProjectMonth::class);
    }

    public function getShortMonthNameAttribute(): string
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];
        return $months[$this->month] ?? '';
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    public function getHasDataAttribute(): bool
    {
        return $this->workerSummaries()->exists()
            || $this->workerPayments()->exists()
            || $this->projectMonths()->exists();
    }

    public function getSummaryCountAttribute(): int
    {
        return $this->workerSummaries()->count();
    }

    public function getPaymentCountAttribute(): int
    {
        return $this->workerPayments()->count();
    }

    public static function firstOrCreateForMonth(int $year, int $month): self
    {
        $periodCode = sprintf('%04d-%02d', $year, $month);
        $startDate = sprintf('%04d-%02d-01', $year, $month);

        return self::firstOrCreate(
            ['period_code' => $periodCode],
            [
                'year' => $year,
                'month' => $month,
                'label' => date('F Y', strtotime($startDate)),
                'start_date' => $startDate,
                'end_date' => date('Y-m-t', strtotime($startDate)),
            ]
        );
    }
}
