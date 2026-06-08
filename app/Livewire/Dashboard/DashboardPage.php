<?php

namespace App\Livewire\Dashboard;

use App\Exports\DashboardStatsExport;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MovementType;
use App\Models\PaymentReminder;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[\Livewire\Attributes\Layout('layouts.app')]
class DashboardPage extends Component
{
    public string $statsDateFrom = '';

    public string $statsDateTo = '';

    public string $filterUserId = '';

    public string $selectedMovementCategory = '';

    public string $selectedInvoiceProject = '';

    public bool $showDetailModal = false;

    public string $detailTitle = '';

    public string $detailType = '';

    public string $detailKey = '';

    public array $detailRows = [];

    public function mount(): void
    {
        $this->statsDateFrom = now()->startOfYear()->format('Y-m-d');
        $this->statsDateTo = now()->endOfMonth()->format('Y-m-d');
        $this->filterUserId = $this->canAccessAllDashboard() ? '' : (string) auth()->id();
    }

    public function render()
    {
        $now = now();
        $period = $this->resolveReportingPeriod();
        $reportMonths = $this->getReportMonths($period['from'], $period['to']);
        $executiveReport = $this->buildExecutiveReport($reportMonths, $period['from'], $period['to']);
        $dashboardHighlights = $this->buildDashboardHighlights($executiveReport);
        $costBreakdown = $this->buildCostBreakdown($reportMonths, $period['from'], $period['to']);
        $typeBreakdown = $this->buildTypeBreakdown($reportMonths, $period['from'], $period['to']);

        $bankAccounts = $this->applyOwnerFilter(BankAccount::query())
            ->orderBy('bank_name')
            ->get();
        $totalBankBalance = $bankAccounts->sum('current_balance');
        $overdueInvoices = $this->applyOwnerFilter(Invoice::with('client'))
            ->where('status', 'pending')
            ->where('date_due', '<', $now)
            ->orderBy('date_due')
            ->limit(5)
            ->get();
        $upcomingReminders = $this->applyOwnerFilterToReminders(PaymentReminder::with('remindable'))
            ->active()
            ->orderBy('reminder_date')
            ->limit(5)
            ->get();
        $users = $this->canAccessAllDashboard()
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.dashboard.dashboard-page', compact(
            'reportMonths',
            'executiveReport',
            'dashboardHighlights',
            'costBreakdown',
            'typeBreakdown',
            'bankAccounts',
            'totalBankBalance',
            'overdueInvoices',
            'upcomingReminders',
            'period',
            'users',
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

    public function showTypeDetails(string $typeLabel): void
    {
        $period = $this->resolveReportingPeriod();
        $normalizedType = $this->normalizeTypeForLookup($typeLabel);

        $typeLabels = MovementType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->flatMap(fn (MovementType $type) => [
                $this->normalizeTypeForLookup((string) $type->slug) => (string) $type->name,
                $this->normalizeTypeForLookup((string) $type->name) => (string) $type->name,
            ])
            ->all();

        $this->detailTitle = $typeLabels[$normalizedType] ?? $typeLabel;
        $this->detailType = 'type';
        $this->detailKey = $typeLabel;

        $rows = $this->applyDateRange($this->applyOwnerFilter(BankMovement::query()), 'date', $period['from'], $period['to'])
            ->whereRaw('COALESCE(NULLIF(type, \'\'), ?) = ?', [__('app.none'), $typeLabel])
            ->selectRaw("COALESCE(NULLIF(category, ''), ?) as category_label", [__('app.none')])
            ->selectRaw('COALESCE(SUM(COALESCE(deposit, 0) - COALESCE(withdrawal, 0)), 0) as total_amount')
            ->groupBy('category_label')
            ->orderByDesc('total_amount')
            ->get();

        $this->detailRows = $rows->map(fn ($row) => [
            'label' => (string) $row->category_label,
            'amount' => round((float) $row->total_amount, 2),
        ])->all();

        $this->showDetailModal = true;
    }

    public function showCategoryDetails(string $categoryLabel): void
    {
        $period = $this->resolveReportingPeriod();

        $this->detailTitle = $categoryLabel;
        $this->detailType = 'category';
        $this->detailKey = $categoryLabel;

        $expenseRows = $this->applyDateRange($this->applyOwnerFilter(Expense::query()), 'date', $period['from'], $period['to'])
            ->whereRaw("COALESCE(NULLIF(category, ''), ?) = ?", [__('app.none'), $categoryLabel])
            ->selectRaw('description as label')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('description')
            ->orderByDesc('total_amount')
            ->get();

        $movementRows = $this->applyDateRange($this->applyOwnerFilter(BankMovement::query()), 'date', $period['from'], $period['to'])
            ->whereRaw("COALESCE(NULLIF(category, ''), ?) = ?", [__('app.none'), $categoryLabel])
            ->selectRaw("COALESCE(NULLIF(concept, ''), type) as label")
            ->selectRaw('COALESCE(SUM(COALESCE(deposit, 0) - COALESCE(withdrawal, 0)), 0) as total_amount')
            ->groupBy('label')
            ->orderByDesc('total_amount')
            ->get();

        $combined = collect();

        foreach ($expenseRows as $row) {
            $key = (string) $row->label;
            $combined[$key] = ($combined[$key] ?? 0) + (float) $row->total_amount;
        }

        foreach ($movementRows as $row) {
            $key = (string) $row->label;
            $combined[$key] = ($combined[$key] ?? 0) + (float) $row->total_amount;
        }

        $this->detailRows = $combined
            ->map(fn ($amount, $label) => [
                'label' => $label,
                'amount' => round((float) $amount, 2),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $this->showDetailModal = true;
    }

    public function showExecutiveRowDetails(string $rowKey): void
    {
        $period = $this->resolveReportingPeriod();

        $this->detailType = 'executive';
        $this->detailKey = $rowKey;

        $typeLabels = MovementType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->flatMap(fn (MovementType $type) => [
                $this->normalizeTypeForLookup((string) $type->slug) => (string) $type->name,
                $this->normalizeTypeForLookup((string) $type->name) => (string) $type->name,
            ])
            ->all();

        $movementLabel = fn (string $type): string => $typeLabels[$this->normalizeTypeForLookup($type)] ?? $type;

        switch ($rowKey) {
            case 'billing':
                $this->detailTitle = __('app.dashboard_billing');
                $rows = $this->applyDateRange($this->applyOwnerFilter(Invoice::query()), 'date_issued', $period['from'], $period['to'])
                    ->selectRaw('invoice_number as label')
                    ->selectRaw('COALESCE(SUM(total), 0) as total_amount')
                    ->groupBy('label')
                    ->orderByDesc('total_amount')
                    ->get();
                break;
            default:
                $this->detailTitle = $movementLabel($rowKey);
                $rows = $this->applyDateRange($this->applyOwnerFilter(BankMovement::query()), 'date', $period['from'], $period['to'])
                    ->whereRaw('LOWER(type) = LOWER(?)', [$rowKey])
                    ->selectRaw("COALESCE(NULLIF(concept, ''), type) as label")
                    ->selectRaw('COALESCE(SUM(COALESCE(deposit, 0) - COALESCE(withdrawal, 0)), 0) as total_amount')
                    ->groupBy('label')
                    ->orderByDesc('total_amount')
                    ->get();
                break;
        }

        $this->detailRows = $rows->map(fn ($row) => [
            'label' => (string) $row->label,
            'amount' => round((float) $row->total_amount, 2),
        ])->all();

        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailRows = [];
        $this->detailTitle = '';
        $this->detailType = '';
        $this->detailKey = '';
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

        $typeLabels = MovementType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->flatMap(fn (MovementType $type) => [
                $this->normalizeTypeForLookup((string) $type->slug) => (string) $type->name,
                $this->normalizeTypeForLookup((string) $type->name) => (string) $type->name,
            ])
            ->all();

        $movementLabel = fn (string $type): string => $typeLabels[$this->normalizeTypeForLookup($type)] ?? $type;

        $invoiceTotals = $this->monthlyTotals(
            $this->applyOwnerFilter(Invoice::query()),
            'date_issued',
            'total',
            $from,
            $to
        );
        $collectedTotals = $this->bankMovementTotalsByType('Nomina', $from, $to);
        $expenseLedgerTotals = $this->bankMovementTotalsByType('S.Social', $from, $to);
        $purchaseMovementTotals = $this->bankMovementTotalsByType('AEAT', $from, $to);
        $cashInTotals = $this->bankMovementTotalsByType('Compra', $from, $to);
        $cashOutTotals = $this->bankMovementTotalsByType('Gasto', $from, $to);
        $totalCostTotals = $this->bankMovementTotalsByType('PROVEEDOR', $from, $to);
        $marginTotals = $this->bankMovementTotalsByType('VARIOS', $from, $to);
        $cashDeltaTotals = $this->bankMovementTotalsByType('IVA', $from, $to);

        $rows = [
            ['key' => 'billing', 'label' => __('app.dashboard_billing'), 'accent' => 'billing', 'values' => $invoiceTotals],
            ['key' => 'collected', 'label' => $movementLabel('Nomina'), 'accent' => 'collected', 'values' => $collectedTotals],
            ['key' => 'ledger_expenses', 'label' => $movementLabel('S.Social'), 'accent' => 'cost', 'values' => $expenseLedgerTotals],
            ['key' => 'purchase_movements', 'label' => $movementLabel('AEAT'), 'accent' => 'cost', 'values' => $purchaseMovementTotals],
            ['key' => 'cash_in', 'label' => $movementLabel('Compra'), 'accent' => 'cash-in', 'values' => $cashInTotals],
            ['key' => 'cash_out', 'label' => $movementLabel('Gasto'), 'accent' => 'cash-out', 'values' => $cashOutTotals],
            ['key' => 'total_cost', 'label' => $movementLabel('PROVEEDOR'), 'accent' => 'total-cost', 'values' => $totalCostTotals, 'emphasis' => true],
            ['key' => 'margin', 'label' => $movementLabel('VARIOS'), 'accent' => 'margin', 'values' => $marginTotals, 'emphasis' => true],
            ['key' => 'cash_delta', 'label' => $movementLabel('IVA'), 'accent' => 'delta', 'values' => $cashDeltaTotals, 'emphasis' => true],
        ];

        $mappedRows = collect($rows)->map(function (array $row) use ($monthKeys) {
            $row['monthly'] = collect($monthKeys)
                ->map(fn (string $monthKey) => round((float) ($row['values'][$monthKey] ?? 0), 2))
                ->all();
            $row['total'] = round(array_sum($row['monthly']), 2);
            unset($row['values']);

            return $row;
        })->all();

        $billingRow = collect($mappedRows)->firstWhere('key', 'billing');
        $otherRows = collect($mappedRows)->where('key', '!=', 'billing');

        $finalResultMonthly = collect($monthKeys)->map(function (string $monthKey) use ($billingRow, $otherRows, $monthKeys) {
            $monthIndex = array_search($monthKey, $monthKeys);
            $billingValue = $billingRow['monthly'][$monthIndex] ?? 0;
            $othersSum = $otherRows->sum(fn ($row) => $row['monthly'][$monthIndex] ?? 0);

            return round($billingValue + $othersSum, 2);
        })->all();

        $finalResultTotal = round(array_sum($finalResultMonthly), 2);

        $mappedRows[] = [
            'key' => 'final_result',
            'label' => 'TOTAL FACTURAS - ALLL',
            'accent' => 'delta',
            'emphasis' => true,
            'monthly' => $finalResultMonthly,
            'total' => $finalResultTotal,
        ];

        return $mappedRows;
    }

    private function bankMovementTotalsByType(string $type, Carbon $from, Carbon $to): array
    {
        $query = $this->applyOwnerFilter(BankMovement::query())
            ->whereRaw('LOWER(type) = LOWER(?)', [$type]);

        $sumExpression = 'COALESCE(deposit, 0) - COALESCE(withdrawal, 0)';

        return $this->monthlyTotals(
            $query,
            'date',
            $sumExpression,
            $from,
            $to
        );
    }

    private function bankMovementSumExpression(): string
    {
        return 'SUM(COALESCE(deposit, 0) - COALESCE(withdrawal, 0))';
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
        $outstanding = (float) $this->applyOwnerFilter(Invoice::query())
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

        $expenseRows = $this->applyDateRange($this->applyOwnerFilter(Expense::query()), 'date', $from, $to)
            ->selectRaw("COALESCE(NULLIF(category, ''), ?) as category_label", [__('app.none')])
            ->selectRaw("{$monthExpr} as ym")
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('category_label', 'ym')
            ->get();

        foreach ($expenseRows as $row) {
            $category = (string) $row->category_label;
            $totals[$category][$row->ym] = round((float) $row->total_amount, 2);
        }

        $movementRows = $this->applyDateRange($this->applyOwnerFilter(BankMovement::query()), 'date', $from, $to)
            ->selectRaw("COALESCE(NULLIF(category, ''), ?) as category_label", [__('app.none')])
            ->selectRaw("{$monthExpr} as ym")
            ->selectRaw($this->bankMovementSumExpression().' as total_amount')
            ->groupBy('category_label', 'ym')
            ->get();

        foreach ($movementRows as $row) {
            $category = (string) $row->category_label;
            $totals[$category][$row->ym] = round((float) ($totals[$category][$row->ym] ?? 0) + (float) $row->total_amount, 2);
        }

        $excludedCategories = [
            'BBVA', 'BBVA MON', 'POPULAR', 'POPULAR MON',
            'SABADELL MON', 'SABADELL', 'SANTANDER',
            'CREDITO BANKSANTANDER', 'SANTANDER MON',
            'BANKINTER CREDITO MON', 'MON BANKINTER', 'BANKINTER CREDITO',
        ];

        return collect($totals)
            ->reject(function (array $values, string $category) use ($excludedCategories) {
                if (in_array(strtoupper($category), array_map('strtoupper', $excludedCategories), true)) {
                    return true;
                }

                return strtoupper($category) === strtoupper(__('app.none'));
            })
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
            ->values()
            ->all();
    }

    private function normalizeTypeForLookup(string $type): string
    {
        return strtoupper(str_replace([' ', '.', '-'], '_', trim($type)));
    }

    private function buildTypeBreakdown(array $reportMonths, Carbon $from, Carbon $to): array
    {
        $monthKeys = collect($reportMonths)->pluck('key')->all();
        $typeLabels = MovementType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->flatMap(fn (MovementType $type) => [
                $this->normalizeTypeForLookup((string) $type->slug) => (string) $type->name,
                $this->normalizeTypeForLookup((string) $type->name) => (string) $type->name,
            ])
            ->all();

        $monthExpr = $this->monthBucketExpression('date');
        $rows = $this->applyDateRange($this->applyOwnerFilter(BankMovement::query()), 'date', $from, $to)
            ->selectRaw("COALESCE(NULLIF(type, ''), ?) as type_slug", [__('app.none')])
            ->selectRaw("{$monthExpr} as ym")
            ->selectRaw($this->bankMovementSumExpression().' as total_amount')
            ->groupBy('type_slug', 'ym')
            ->get();

        $totals = [];
        foreach ($rows as $row) {
            $typeSlug = (string) $row->type_slug;
            $totals[$typeSlug][$row->ym] = round((float) $row->total_amount, 2);
        }

        return collect($totals)
            ->map(function (array $values, string $typeSlug) use ($monthKeys, $typeLabels) {
                $monthly = collect($monthKeys)
                    ->map(fn (string $monthKey) => round((float) ($values[$monthKey] ?? 0), 2))
                    ->all();

                $normalizedSlug = $this->normalizeTypeForLookup($typeSlug);

                return [
                    'label' => $typeLabels[$normalizedSlug] ?? str($typeSlug)->replace('_', ' ')->headline()->toString(),
                    'monthly' => $monthly,
                    'total' => round(array_sum($monthly), 2),
                ];
            })
            ->sortByDesc('total')
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

    private function canAccessAllDashboard(): bool
    {
        return (bool) auth()->user()?->isAdmin() || Gate::allows('dashboard.access_all');
    }

    private function applyOwnerFilter(Builder $query): Builder
    {
        if ($this->filterUserId !== '') {
            $query->where($query->getModel()->getTable().'.user_id', (int) $this->filterUserId);
        }

        return $query;
    }

    private function applyOwnerFilterToReminders(Builder $query): Builder
    {
        if ($this->filterUserId === '') {
            return $query;
        }

        return $query->whereHasMorph(
            'remindable',
            [Invoice::class, Expense::class],
            fn (Builder $remindableQuery) => $remindableQuery->where('user_id', (int) $this->filterUserId)
        );
    }
}
