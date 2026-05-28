<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WorkerPaymentExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $payments
    ) {}

    public function collection(): Collection
    {
        return $this->payments;
    }

    public function headings(): array
    {
        return [
            __('app.period'),
            __('app.worker'),
            __('app.project_month'),
            __('app.payment_date'),
            __('app.payment_type'),
            __('app.amount'),
            __('app.reference'),
            __('app.notes'),
        ];
    }

    public function map($payment): array
    {
        $projectMonthLabel = '';
        if ($payment->projectMonth) {
            $projectMonthLabel = ($payment->projectMonth->client?->name ?? '') . ' / ' . ($payment->projectMonth->project?->name ?? '');
        }

        return [
            $payment->monthlyPeriod?->period_code ?? '',
            $payment->worker?->full_name ?? '',
            $projectMonthLabel,
            $payment->payment_date?->format('d/m/Y') ?? '',
            $payment->payment_type_label,
            (float) $payment->amount,
            $payment->reference ?? '',
            $payment->notes ?? '',
        ];
    }
}
