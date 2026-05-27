<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectMonthExport implements FromCollection, WithHeadings, WithMapping
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
            __('app.sheet_code'),
            __('app.total_nominal'),
            __('app.total_social_security'),
            __('app.total_expenses'),
            __('app.total_invoiced'),
            __('app.estimated_invoice'),
            __('app.difference'),
            __('app.total_hours'),
        ];
    }

    public function map($row): array
    {
        return [
            $row->monthlyPeriod?->period_code ?? '',
            $row->client?->name ?? '',
            $row->project?->name ?? '',
            $row->sheet_code ?? '',
            (float) $row->total_nominal,
            (float) $row->total_social_security,
            (float) $row->total_expenses,
            (float) $row->total_invoiced,
            (float) $row->estimated_invoice,
            (float) $row->difference,
            (float) $row->total_hours,
        ];
    }
}
