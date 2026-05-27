<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WorkerMonthlySummaryExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $summaries
    ) {}

    public function collection(): Collection
    {
        return $this->summaries;
    }

    public function headings(): array
    {
        return [
            __('app.period'),
            __('app.worker'),
            __('app.total_amount'),
            __('app.paid_amount'),
            __('app.total_hours'),
            __('app.payroll_amount'),
            __('app.advance_amount'),
            __('app.credit_amount'),
            __('app.ticket_amount'),
            __('app.difference'),
            __('app.final_difference'),
        ];
    }

    public function map($summary): array
    {
        return [
            $summary->monthlyPeriod?->period_code ?? '',
            $summary->worker?->full_name ?? '',
            (float) $summary->total_amount,
            (float) $summary->paid_amount,
            (float) $summary->total_hours,
            (float) $summary->payroll_amount,
            (float) $summary->advance_amount,
            (float) $summary->credit_amount,
            (float) $summary->ticket_amount,
            (float) $summary->difference,
            (float) $summary->final_difference,
        ];
    }
}
