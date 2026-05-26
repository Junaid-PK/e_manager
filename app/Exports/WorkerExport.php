<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WorkerExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $workers
    ) {}

    public function collection(): Collection
    {
        return $this->workers;
    }

    public function headings(): array
    {
        return [
            __('app.full_name'),
            __('app.nie'),
            __('app.bank_account'),
            __('app.created_at'),
        ];
    }

    public function map($worker): array
    {
        return [
            $worker->full_name,
            $worker->nie ?? '',
            $worker->bank_account ?? '',
            $worker->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }
}
