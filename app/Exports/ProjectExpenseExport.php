<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectExpenseExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $rows
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            __('app.period'),
            __('app.client'),
            __('app.project'),
            __('app.date'),
            __('app.category'),
            __('app.description'),
            __('app.amount'),
        ];
    }

    public function map($row): array
    {
        return [
            $row->projectMonth?->monthlyPeriod?->period_code ?? '',
            $row->projectMonth?->client?->name ?? '',
            $row->projectMonth?->project?->name ?? '',
            $row->expense_date ? $row->expense_date->format('Y-m-d') : '',
            $row->category ?? '',
            $row->description ?? '',
            (float) $row->amount,
        ];
    }
}
