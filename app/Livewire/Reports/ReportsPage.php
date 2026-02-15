<?php

namespace App\Livewire\Reports;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use Livewire\Component;

class ReportsPage extends Component
{
    public string $activeReport = 'monthly';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $filterCompanyId = '';
    public string $year = '';
    public bool $showResults = false;
    public array $reportData = [];

    public function mount(): void
    {
        $this->year = (string) now()->year;
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function switchReport(string $report): void
    {
        $this->activeReport = $report;
        $this->showResults = false;
        $this->reportData = [];
    }

    public function generate(): void
    {
        $this->reportData = match ($this->activeReport) {
            'monthly' => $this->generateMonthlySummary(),
            'yearly' => $this->generateYearlySummary(),
            'income_expenses' => $this->generateIncomeExpenses(),
            'client_billing' => $this->generateClientBilling(),
            'bank_reconciliation' => $this->generateBankReconciliation(),
            default => [],
        };
        $this->showResults = true;
    }

    private function generateMonthlySummary(): array
    {
        $query = Invoice::query();
        if ($this->filterCompanyId) {
            $query->where('company_id', $this->filterCompanyId);
        }
        if ($this->dateFrom) {
            $query->where('date_issued', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('date_issued', '<=', $this->dateTo);
        }

        $invoices = $query->get();
        $expenses = Expense::query()
            ->when($this->filterCompanyId, fn($q) => $q->where('company_id', $this->filterCompanyId))
            ->when($this->dateFrom, fn($q) => $q->where('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('date', '<=', $this->dateTo))
            ->get();

        return [
            'total_invoiced' => $invoices->sum('total'),
            'total_paid' => $invoices->where('status', 'paid')->sum('total'),
            'total_pending' => $invoices->whereIn('status', ['pending', 'partial'])->sum('total'),
            'total_overdue' => $invoices->where('status', 'pending')->where('date_due', '<', now())->sum('total'),
            'total_iva' => $invoices->sum('iva_amount'),
            'total_retention' => $invoices->sum('retention_amount'),
            'total_expenses' => $expenses->sum('amount'),
            'net_income' => $invoices->where('status', 'paid')->sum('total') - $expenses->sum('amount'),
            'invoice_count' => $invoices->count(),
            'expense_count' => $expenses->count(),
        ];
    }

    private function generateYearlySummary(): array
    {
        $year = $this->year ?: now()->year;
        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $invoiceQuery = Invoice::whereYear('date_issued', $year)->whereMonth('date_issued', $m);
            $expenseQuery = Expense::whereYear('date', $year)->whereMonth('date', $m);

            if ($this->filterCompanyId) {
                $invoiceQuery->where('company_id', $this->filterCompanyId);
                $expenseQuery->where('company_id', $this->filterCompanyId);
            }

            $months[] = [
                'month' => $m,
                'label' => \Carbon\Carbon::create($year, $m)->format('M'),
                'invoiced' => (float) $invoiceQuery->sum('total'),
                'paid' => (float) (clone $invoiceQuery)->where('status', 'paid')->sum('total'),
                'expenses' => (float) $expenseQuery->sum('amount'),
            ];
        }

        return ['year' => $year, 'months' => $months];
    }

    private function generateIncomeExpenses(): array
    {
        $invoices = Invoice::query()
            ->when($this->filterCompanyId, fn($q) => $q->where('company_id', $this->filterCompanyId))
            ->when($this->dateFrom, fn($q) => $q->where('date_issued', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('date_issued', '<=', $this->dateTo))
            ->where('status', 'paid')
            ->sum('total');

        $expensesByCategory = Expense::query()
            ->when($this->filterCompanyId, fn($q) => $q->where('company_id', $this->filterCompanyId))
            ->when($this->dateFrom, fn($q) => $q->where('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('date', '<=', $this->dateTo))
            ->selectRaw('COALESCE(category, ?) as category, SUM(amount) as total', [__('app.other')])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $totalExpenses = array_sum(array_column($expensesByCategory, 'total'));

        return [
            'total_income' => (float) $invoices,
            'total_expenses' => (float) $totalExpenses,
            'net' => (float) $invoices - (float) $totalExpenses,
            'expense_categories' => $expensesByCategory,
        ];
    }

    private function generateClientBilling(): array
    {
        $query = Invoice::with('client')
            ->when($this->filterCompanyId, fn($q) => $q->where('company_id', $this->filterCompanyId))
            ->when($this->dateFrom, fn($q) => $q->where('date_issued', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('date_issued', '<=', $this->dateTo));

        $clients = $query->get()
            ->groupBy('client_id')
            ->map(function ($invoices) {
                $client = $invoices->first()->client;
                return [
                    'client_name' => $client?->name ?? 'Unknown',
                    'total_invoiced' => $invoices->sum('total'),
                    'total_paid' => $invoices->where('status', 'paid')->sum('total'),
                    'total_pending' => $invoices->whereIn('status', ['pending', 'partial'])->sum('total'),
                    'invoice_count' => $invoices->count(),
                ];
            })
            ->sortByDesc('total_invoiced')
            ->values()
            ->toArray();

        return ['clients' => $clients];
    }

    private function generateBankReconciliation(): array
    {
        $accounts = BankAccount::all()->map(function ($account) {
            $movements = BankMovement::where('bank_account_id', $account->id)
                ->when($this->dateFrom, fn($q) => $q->where('date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($q) => $q->where('date', '<=', $this->dateTo));

            return [
                'bank_name' => $account->bank_name,
                'account_number' => $account->masked_account_number,
                'initial_balance' => (float) $account->initial_balance,
                'current_balance' => (float) $account->current_balance,
                'total_deposits' => (float) $movements->sum('deposit'),
                'total_withdrawals' => (float) $movements->sum('withdrawal'),
                'movement_count' => $movements->count(),
            ];
        })->toArray();

        return ['accounts' => $accounts];
    }

    public function render()
    {
        return view('livewire.reports.reports-page', [
            'companies' => Company::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
