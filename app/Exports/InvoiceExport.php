<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoiceExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $invoices
    ) {}

    public function collection(): Collection
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            __('app.invoice_number'),
            __('app.project'),
            __('app.client'),
            __('app.month'),
            __('app.amount'),
            __('app.iva'),
            __('app.retention'),
            __('app.total'),
            __('app.status'),
            __('app.payment_type'),
            __('app.bank_name'),
            __('app.cobrado'),
            __('app.company'),
            __('app.resto'),
            __('app.date_issued'),
            __('app.date_due'),
            __('app.bank_date'),
            __('app.notes'),
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->project?->name ?? '',
            $invoice->client?->name ?? '',
            $invoice->month ?? '',
            (float) $invoice->amount,
            (float) $invoice->iva_amount,
            (float) $invoice->retention_amount,
            (float) $invoice->total,
            $invoice->status,
            $invoice->payment_type ?? '',
            $invoice->bank_name ?? '',
            (float) ($invoice->amount_paid ?? 0),
            $invoice->company?->name ?? '',
            (float) ($invoice->amount_remaining ?? 0),
            $invoice->date_issued?->format('Y-m-d') ?? '',
            $invoice->date_due?->format('Y-m-d') ?? '',
            $invoice->bank_date?->format('Y-m-d') ?? '',
            $invoice->notes ?? '',
        ];
    }
}
