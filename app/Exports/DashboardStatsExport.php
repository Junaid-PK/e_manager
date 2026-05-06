<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class DashboardStatsExport implements FromArray
{
    public function __construct(
        private array $reportMonths,
        private array $executiveReport,
        private array $costBreakdown,
        private string $dateFrom,
        private string $dateTo
    ) {}

    public function array(): array
    {
        $rows = [];
        $rows[] = [__('app.dashboard'), __('app.from'), $this->dateFrom ?: '-', __('app.to'), $this->dateTo ?: '-'];
        $rows[] = [];

        $monthHeaders = array_map(fn ($month) => $month['label'] ?? '', $this->reportMonths);

        $rows[] = [__('app.dashboard_executive_report')];
        $rows[] = array_merge([__('app.metric'), __('app.total')], $monthHeaders);
        foreach ($this->executiveReport as $item) {
            $rows[] = array_merge(
                [$item['label'] ?? '', $item['total'] ?? 0],
                $item['monthly'] ?? []
            );
        }
        $rows[] = [];

        $rows[] = [__('app.dashboard_cost_structure')];
        $rows[] = array_merge([__('app.category'), __('app.total')], $monthHeaders);
        foreach ($this->costBreakdown as $item) {
            $rows[] = array_merge(
                [$item['label'] ?? '', $item['total'] ?? 0],
                $item['monthly'] ?? []
            );
        }

        return $rows;
    }
}
