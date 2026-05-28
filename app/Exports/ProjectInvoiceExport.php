<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectInvoiceExport implements FromCollection, WithHeadings, WithMapping
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
            __('app.invoice_number'),
            __('app.date'),
            __('app.estimated_amount'),
            __('app.actual_amount'),
            __('app.difference'),
            __('app.status'),
            __('app.notes'),
        ];
    }

    public function map($row): array
    {
        return [
            $row->projectMonth?->monthlyPeriod?->period_code ?? '',
            $row->projectMonth?->client?->name ?? '',
            $row->projectMonth?->project?->name ?? '',
            $row->invoice_no ?? '',
            $row->invoice_date ? $row->invoice_date->format('Y-m-d') : '',
            (float) $row->estimated_amount,
            (float) $row->actual_amount,
            (float) $row->difference,
            $row->status,
            $row->notes ?? '',
        ];
    }
}
