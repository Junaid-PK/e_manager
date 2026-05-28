<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WorkerProjectEntryExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $entries
    ) {}

    public function collection(): Collection
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return [
            __('app.period'),
            __('app.client'),
            __('app.project'),
            __('app.worker'),
            __('app.nie'),
            __('app.special_note'),
            __('app.social_security'),
            __('app.hours'),
            __('app.days'),
            __('app.rate'),
            __('app.total_amount'),
            __('app.paid_amount'),
            __('app.remaining'),
        ];
    }

    public function map($entry): array
    {
        return [
            $entry->projectMonth?->monthlyPeriod?->period_code ?? '',
            $entry->projectMonth?->client?->name ?? '',
            $entry->projectMonth?->project?->name ?? '',
            $entry->worker?->full_name ?? '',
            $entry->worker?->nie ?? '',
            $entry->special_note ?? '',
            $entry->social_security,
            $entry->hours,
            $entry->days,
            $entry->rate,
            $entry->total_amount,
            $entry->paid_amount,
            $entry->remaining_amount,
        ];
    }
}
