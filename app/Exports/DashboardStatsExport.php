<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class DashboardStatsExport implements FromArray
{
    public function __construct(
        private array $movementCategoryStats,
        private array $movementTypeStats,
        private array $invoiceProjectStats,
        private array $invoicePaymentTypeStats,
        private string $dateFrom,
        private string $dateTo,
        private string $movementCategory,
        private string $invoiceProject
    ) {}

    public function array(): array
    {
        $rows = [];
        $rows[] = [__('app.dashboard'), __('app.from'), $this->dateFrom ?: '-', __('app.to'), $this->dateTo ?: '-'];
        $rows[] = [];

        $rows[] = [__('app.movements'), __('app.category'), $this->movementCategory ?: __('app.all')];
        $rows[] = [__('app.category'), __('app.count'), __('app.deposits'), __('app.withdrawals'), __('app.net')];
        foreach ($this->movementCategoryStats as $item) {
            $rows[] = [$item['name'] ?? '', $item['count'] ?? 0, $item['deposits'] ?? 0, $item['withdrawals'] ?? 0, $item['net'] ?? 0];
        }
        $rows[] = [];

        $rows[] = [__('app.movements'), __('app.type')];
        $rows[] = [__('app.type'), __('app.count'), __('app.deposits'), __('app.withdrawals'), __('app.net')];
        foreach ($this->movementTypeStats as $item) {
            $rows[] = [$item['name'] ?? '', $item['count'] ?? 0, $item['deposits'] ?? 0, $item['withdrawals'] ?? 0, $item['net'] ?? 0];
        }
        $rows[] = [];

        $rows[] = [__('app.invoices'), __('app.project'), $this->invoiceProject ?: __('app.all')];
        $rows[] = [__('app.project'), __('app.count'), __('app.total'), __('app.amount_remaining')];
        foreach ($this->invoiceProjectStats as $item) {
            $rows[] = [$item['name'] ?? '', $item['count'] ?? 0, $item['total'] ?? 0, $item['remaining'] ?? 0];
        }
        $rows[] = [];

        $rows[] = [__('app.invoices'), __('app.payment_type')];
        $rows[] = [__('app.payment_type'), __('app.count'), __('app.total'), __('app.amount_remaining')];
        foreach ($this->invoicePaymentTypeStats as $item) {
            $rows[] = [$item['name'] ?? '', $item['count'] ?? 0, $item['total'] ?? 0, $item['remaining'] ?? 0];
        }

        return $rows;
    }
}
