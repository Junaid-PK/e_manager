<?php

namespace App\Livewire\Dashboard;

use App\Exports\DashboardStatsExport;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentReminder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Models\MovementType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;

#[\Livewire\Attributes\Layout('layouts.app')]
class DashboardPage extends Component
{
    public string $statsDateFrom = '';
    public string $statsDateTo = '';
    public string $selectedMovementCategory = '';
    public string $selectedInvoiceProject = '';

    public function mount(): void
    {
        $this->statsDateFrom = now()->startOfYear()->format('Y-m-d');
        $this->statsDateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function render()
    {
        $now        = now();
        $period = $this->resolveReportingPeriod();
        $reportMonths = $this->getReportMonths($period['from'], $period['to']);
        $executiveReport = $this->buildExecutiveReport($reportMonths, $period['from'], $period['to']);
        $dashboardHighlights = $this->buildDashboardHighlights($executiveReport);
        $costBreakdown = $this->buildCostBreakdown($reportMonths, $period['from'], $period['to']);

        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        $totalBankBalance = $bankAccounts->sum('current_balance');
        $overdueInvoices = Invoice::with('client')
                            ->where('status', 'pending')
                            ->where('date_due', '<', $now)
                            ->orderBy('date_due')
                            ->limit(5)
                            ->get();
        $upcomingReminders = PaymentReminder::with('remindable')
                                ->active()
                                ->orderBy('reminder_date')
                                ->limit(5)
                                ->get();

        return view('livewire.dashboard.dashboard-page', compact(
            'reportMonths',
            'executiveReport',
            'dashboardHighlights',
            'costBreakdown',
            'bankAccounts',
            'totalBankBalance',
            'overdueInvoices',
            'upcomingReminders',
            'period',
        ));
    }

    public function exportStatsToExcel()
    {
        $period = $this->resolveReportingPeriod();
        $reportMonths = $this->getReportMonths($period['from'], $period['to']);
        $executiveReport = $this->buildExecutiveReport($reportMonths, $period['from'], $period['to']);
        $costBreakdown = $this->buildCostBreakdown($reportMonths, $period['from'], $period['to']);

        $filename = 'dashboard-stats-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new DashboardStatsExport(
            $reportMonths,
            $executiveReport,
            $costBreakdown,
            $period['from']->format('Y-m-d'),
            $period['to']->format('Y-m-d')
        ), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function quickMarkPaid(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $invoice->update(['status' => 'paid', 'amount_paid' => $invoice->total, 'amount_remaining' => 0]);
        ActivityLog::log('invoice_paid', "Invoice #{$invoice->invoice_number} marked as paid", $invoice);
    }

    private function resolveReportingPeriod(): array
    {
        $from = $this->statsDateFrom !== ''
            ? Carbon::parse($this->statsDateFrom)->startOfMonth()
            : now()->startOfYear();
        $to = $this->statsDateTo !== ''
            ? Carbon::parse($this->statsDateTo)->endOfMonth()
            : now()->endOfMonth();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfMonth(), $from->copy()->endOfMonth()];
        }

        return ['from' => $from, 'to' => $to];
    }

    private function getReportMonths(Carbon $from, Carbon $to): array
    {
        return collect(CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to->copy()->startOfMonth()))
            ->map(fn (Carbon $month) => [
                'key' => $month->format('Y-m'),
                'label' => mb_strtolower($month->translatedFormat('M-y')),
                'full' => $month->translatedFormat('F Y'),
            ])
            ->values()
            ->all();
    }

    private function buildExecutiveReport(array $reportMonths, Carbon $from, Carbon $to): array
    {
        $monthKeys = collect($reportMonths)->pluck('key')->all();
        $invoiceTotals = $this->monthlyTotals(
            Invoice::query(),
            'date_issued',
            'total',
            $from,
            $to
        );
        $expenseTotals = $this->monthlyTotals(
            Expense::query(),
            'date',
            'amount',
            $from,
            $to
        );
        $purchaseMovementTotals = $this->monthlyTotals(
            BankMovement::query()->whereIn('type', ['buy', 'compra']),
            'date',
            'CASE WHEN COALESCE(withdrawal, 0) > 0 THEN withdrawal ELSE COALESCE(deposit, 0) END',
            $from,
            $to
        );
        $cashInTotals = $this->monthlyTotals(
            BankMovement::query(),
            'date',
            'deposit',
            $from,
            $to
        );
        $cashOutTotals = $this->monthlyTotals(
            BankMovement::query(),
            'date',
            'withdrawal',
            $from,
            $to
        );
        $collectedTotals = $this->monthlyTotals(
            Invoice::query(),
            'date_issued',
            'amount_paid',
            $from,
            $to
        );

        $rows = [
            ['key' => 'billing', 'label' => __('app.dashboard_billing'), 'accent' => 'billing', 'values' => $invoiceTotals],
            ['key' => 'collected', 'label' => __('app.cobrado'), 'accent' => 'collected', 'values' => $collectedTotals],
            ['key' => 'ledger_expenses', 'label' => __('app.dashboard_ledger_expenses'), 'accent' => 'cost', 'values' => $expenseTotals],
            ['key' => 'purchase_movements', 'label' => __('app.dashboard_purchase_movements'), 'accent' => 'cost', 'values' => $purchaseMovementTotals],
            ['key' => 'cash_in', 'label' => __('app.dashboard_cash_in'), 'accent' => 'cash-in', 'values' => $cashInTotals],
            ['key' => 'cash_out', 'label' => __('app.dashboard_cash_out'), 'accent' => 'cash-out', 'values' => $cashOutTotals],
        ];

        $costTotals = [];
        $marginTotals = [];
        $cashDeltaTotals = [];
        foreach ($monthKeys as $key) {
            $costTotals[$key] = round(($expenseTotals[$key] ?? 0) + ($purchaseMovementTotals[$key] ?? 0), 2);
            $marginTotals[$key] = round(($invoiceTotals[$key] ?? 0) - $costTotals[$key], 2);
            $cashDeltaTotals[$key] = round(($cashInTotals[$key] ?? 0) - ($cashOutTotals[$key] ?? 0), 2);
        }

        $rows[] = ['key' => 'total_cost', 'label' => __('app.dashboard_total_cost'), 'accent' => 'total-cost', 'values' => $costTotals, 'emphasis' => true];
        $rows[] = ['key' => 'margin', 'label' => __('app.dashboard_operating_margin'), 'accent' => 'margin', 'values' => $marginTotals, 'emphasis' => true];
        $rows[] = ['key' => 'cash_delta', 'label' => __('app.dashboard_cash_delta'), 'accent' => 'delta', 'values' => $cashDeltaTotals, 'emphasis' => true];

        return collect($rows)->map(function (array $row) use ($monthKeys) {
            $row['monthly'] = collect($monthKeys)
                ->map(fn (string $monthKey) => round((float) ($row['values'][$monthKey] ?? 0), 2))
                ->all();
            $row['total'] = round(array_sum($row['monthly']), 2);
            unset($row['values']);

            return $row;
        })->all();
    }

    private function buildDashboardHighlights(array $executiveReport): array
    {
        $byKey = collect($executiveReport)->keyBy('key');
        $billing = (float) ($byKey->get('billing')['total'] ?? 0);
        $cost = (float) ($byKey->get('total_cost')['total'] ?? 0);
        $margin = (float) ($byKey->get('margin')['total'] ?? 0);
        $cashDelta = (float) ($byKey->get('cash_delta')['total'] ?? 0);
        $collected = (float) ($byKey->get('collected')['total'] ?? 0);
        $collectionRate = $billing > 0 ? round($collected / $billing * 100, 1) : 0.0;
        $marginRate = $billing > 0 ? round($margin / $billing * 100, 1) : 0.0;
        $outstanding = (float) Invoice::query()
            ->whereIn('status', ['pending', 'partial'])
            ->sum('amount_remaining');

        return [
            [
                'label' => __('app.dashboard_highlight_billing'),
                'value' => $billing,
                'meta' => __('app.dashboard_highlight_billing_meta'),
                'tone' => 'billing',
            ],
            [
                'label' => __('app.dashboard_highlight_cost'),
                'value' => $cost,
                'meta' => __('app.dashboard_highlight_cost_meta'),
                'tone' => 'cost',
            ],
            [
                'label' => __('app.dashboard_highlight_margin'),
                'value' => $margin,
                'meta' => __('app.dashboard_percentage_meta', ['value' => $marginRate]),
                'tone' => $margin >= 0 ? 'positive' : 'negative',
            ],
            [
                'label' => __('app.dashboard_highlight_collection'),
                'value' => $collected,
                'meta' => __('app.dashboard_percentage_meta', ['value' => $collectionRate]),
                'tone' => 'collected',
            ],
            [
                'label' => __('app.dashboard_highlight_cash_delta'),
                'value' => $cashDelta,
                'meta' => __('app.dashboard_highlight_cash_delta_meta'),
                'tone' => $cashDelta >= 0 ? 'positive' : 'negative',
            ],
            [
                'label' => __('app.dashboard_highlight_outstanding'),
                'value' => $outstanding,
                'meta' => __('app.dashboard_highlight_outstanding_meta'),
                'tone' => 'outstanding',
            ],
        ];
    }

    private function buildCostBreakdown(array $reportMonths, Carbon $from, Carbon $to): array
    {
        $monthKeys = collect($reportMonths)->pluck('key')->all();
        $totals = [];
        $monthExpr = $this->monthBucketExpression('date');

        $expenseRows = $this->applyDateRange(Expense::query(), 'date', $from, $to)
            ->selectRaw("COALESCE(NULLIF(category, ''), ?) as category_label", [__('app.none')])
            ->selectRaw("{$monthExpr} as ym")
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('category_label', 'ym')
            ->get();

        foreach ($expenseRows as $row) {
            $category = (string) $row->category_label;
            $totals[$category][$row->ym] = round((float) $row->total_amount, 2);
        }

        $movementRows = $this->applyDateRange(BankMovement::query()->whereIn('type', ['buy', 'compra']), 'date', $from, $to)
            ->selectRaw("COALESCE(NULLIF(category, ''), ?) as category_label", [__('app.none')])
            ->selectRaw("{$monthExpr} as ym")
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(withdrawal, 0) > 0 THEN withdrawal ELSE COALESCE(deposit, 0) END), 0) as total_amount')
            ->groupBy('category_label', 'ym')
            ->get();

        foreach ($movementRows as $row) {
            $category = (string) $row->category_label;
            $totals[$category][$row->ym] = round((float) ($totals[$category][$row->ym] ?? 0) + (float) $row->total_amount, 2);
        }

        return collect($totals)
            ->map(function (array $values, string $category) use ($monthKeys) {
                $monthly = collect($monthKeys)
                    ->map(fn (string $monthKey) => round((float) ($values[$monthKey] ?? 0), 2))
                    ->all();

                return [
                    'label' => $category,
                    'monthly' => $monthly,
                    'total' => round(array_sum($monthly), 2),
                ];
            })
            ->sortByDesc('total')
            ->take(14)
            ->values()
            ->all();
    }

    private function monthlyTotals(Builder $query, string $dateColumn, string $sumExpression, Carbon $from, Carbon $to): array
    {
        $monthExpr = $this->monthBucketExpression($dateColumn);
        $rows = $this->applyDateRange($query, $dateColumn, $from, $to)
            ->selectRaw("{$monthExpr} as ym")
            ->selectRaw("COALESCE(SUM({$sumExpression}), 0) as total_amount")
            ->groupBy('ym')
            ->get();

        return $rows->mapWithKeys(fn ($row) => [$row->ym => round((float) $row->total_amount, 2)])->all();
    }

    private function monthBucketExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function applyDateRange(Builder $query, string $column, ?Carbon $from = null, ?Carbon $to = null): Builder
    {
        $from ??= $this->statsDateFrom !== '' ? Carbon::parse($this->statsDateFrom) : null;
        $to ??= $this->statsDateTo !== '' ? Carbon::parse($this->statsDateTo) : null;

        if ($from !== null) {
            $query->whereDate($column, '>=', $from->toDateString());
        }
        if ($to !== null) {
            $query->whereDate($column, '<=', $to->toDateString());
        }

        return $query;
    }
}
