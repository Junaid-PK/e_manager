<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpenseListadoExport implements FromCollection, WithHeadings, WithMapping
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
            __('app.expense_export_row_kind'),
            __('app.date'),
            __('app.bank'),
            __('app.client'),
            __('app.total_amount'),
            __('app.expense_export_doc_date'),
            __('app.invoice_number'),
            'Trim',
            __('app.vendor'),
            __('app.cif'),
            __('app.concept'),
            __('app.expense_export_bi'),
            __('app.iva'),
            __('app.expense_export_irpf'),
            __('app.expense_export_otros'),
            __('app.total'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function map($row): array
    {
        $row = is_array($row) ? $row : [];
        $kind = ($row['kind'] ?? '') === 'm'
            ? __('app.movement')
            : __('app.expense');

        return [
            $kind,
            $row['date'] ?? '',
            $row['bank_name'] ?? '',
            $row['client'] ?? '',
            $this->parseNumeric($row['total_amt'] ?? ''),
            $row['value_date'] ?? '',
            $row['reference'] ?? '',
            $row['trim'] ?? '',
            $row['beneficiary'] ?? '',
            $row['cif'] ?? '',
            $row['concept'] ?? '',
            $this->parseNumeric($row['bi'] ?? ''),
            $this->parseNumeric($row['iva'] ?? ''),
            $this->parseNumeric($row['irpf'] ?? ''),
            $this->parseNumeric($row['otros'] ?? ''),
            $this->parseNumeric($row['total'] ?? ''),
        ];
    }

    private function parseNumeric(mixed $v): mixed
    {
        if ($v === null || $v === '') {
            return '';
        }
        $s = is_string($v) ? str_replace([' ', ','], ['', '.'], trim($v)) : (string) $v;

        return is_numeric($s) ? (float) $s : '';
    }
}
