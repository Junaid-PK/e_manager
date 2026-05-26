<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonthlyPeriodExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $periods
    ) {}

    public function collection(): Collection
    {
        return $this->periods;
    }

    public function headings(): array
    {
        return [
            __('app.period_code'),
            __('app.year'),
            __('app.month'),
            __('app.label'),
            __('app.start_date'),
            __('app.end_date'),
        ];
    }

    public function map($period): array
    {
        return [
            $period->period_code,
            $period->year,
            $period->month,
            $period->label,
            $period->start_date?->format('Y-m-d') ?? '',
            $period->end_date?->format('Y-m-d') ?? '',
        ];
    }
}
