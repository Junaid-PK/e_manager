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
            __('app.company'),
            __('app.project_id'),
            __('app.project'),
            __('app.client'),
            __('app.month'),
            __('app.date_issued'),
            __('app.date_due'),
            __('app.bank_date'),
            __('app.bank_name'),
            __('app.amount'),
            __('app.iva'),
            __('app.retention'),
            __('app.total'),
            __('app.payment_type'),
            __('app.cobrado'),
            __('app.resto'),
            __('app.status'),
            __('app.notes'),
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->company?->name ?? '',
            $invoice->project_id ?? '',
            $invoice->project?->name ?? '',
            $invoice->client?->name ?? '',
            $invoice->month ?? '',
            $invoice->date_issued?->format('Y-m-d') ?? '',
            $invoice->date_due?->format('Y-m-d') ?? '',
            $invoice->bank_date?->format('Y-m-d') ?? '',
            $invoice->bank_name ?? '',
            (float) $invoice->amount,
            (float) $invoice->iva_amount,
            (float) $invoice->retention_amount,
            (float) $invoice->total,
            $invoice->payment_type ?? '',
            (float) ($invoice->amount_paid ?? 0),
            (float) ($invoice->amount_remaining ?? 0),
            $invoice->status,
            $invoice->notes ?? '',
        ];
    }
}
