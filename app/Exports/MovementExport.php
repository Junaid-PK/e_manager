<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MovementExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $movements
    ) {}

    public function collection(): Collection
    {
        return $this->movements;
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('app.bank_account'),
            __('app.type'),
            __('app.category'),
            __('app.concept'),
            __('app.beneficiary'),
            __('app.reference'),
            __('app.deposit'),
            __('app.withdrawal'),
            __('app.balance'),
        ];
    }

    public function map($movement): array
    {
        $balance = $movement->running_balance ?? $movement->balance ?? null;
        return [
            $movement->date?->format('Y-m-d') ?? '',
            $movement->bankAccount?->bank_name ?? '',
            $movement->type ?? '',
            $movement->category ?? '',
            $movement->concept ?? '',
            $movement->beneficiary ?? '',
            $movement->reference ?? '',
            $movement->deposit !== null ? (float) $movement->deposit : '',
            $movement->withdrawal !== null ? (float) $movement->withdrawal : '',
            $balance !== null ? (float) $balance : '',
        ];
    }
}
