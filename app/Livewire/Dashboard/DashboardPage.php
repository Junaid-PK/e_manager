<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentReminder;
use Livewire\Component;
use App\Models\MovementType;
use Illuminate\Support\Facades\DB;

#[\Livewire\Attributes\Layout('layouts.app')]
class DashboardPage extends Component
{
    public function render()
    {
        $now        = now();
        $thisMonth  = ['year' => $now->year, 'month' => $now->month];
        $lastMonth  = [$now->copy()->subMonth()->year, $now->copy()->subMonth()->month];

        // ── Invoice KPIs ──────────────────────────────────────────────
        $totalReceivable   = Invoice::whereIn('status', ['pending', 'partial'])->sum('amount_remaining');
        $totalCollected    = Invoice::where('status', 'paid')
                                ->whereYear('date_issued', $thisMonth['year'])
                                ->whereMonth('date_issued', $thisMonth['month'])
                                ->sum('amount_paid');
        $overdueCount      = Invoice::where('status', 'pending')->where('date_due', '<', $now)->count();
        $overdueAmount     = Invoice::where('status', 'pending')->where('date_due', '<', $now)->sum('amount_remaining');
        $pendingCount      = Invoice::whereIn('status', ['pending', 'partial'])->count();

        // Collection rate (paid / total issued this year)
        $issuedThisYear    = Invoice::whereYear('date_issued', $now->year)->sum('total');
        $paidThisYear      = Invoice::where('status', 'paid')->whereYear('date_issued', $now->year)->sum('total');
        $collectionRate    = $issuedThisYear > 0 ? round($paidThisYear / $issuedThisYear * 100) : 0;

        // ── Bank KPIs ─────────────────────────────────────────────────
        $bankAccounts      = BankAccount::orderBy('bank_name')->get();
        $totalBankBalance  = $bankAccounts->sum('current_balance');

        // This month movements
        $depositsThisMonth    = BankMovement::whereYear('date', $thisMonth['year'])
                                    ->whereMonth('date', $thisMonth['month'])
                                    ->sum('deposit');
        $withdrawalsThisMonth = BankMovement::whereYear('date', $thisMonth['year'])
                                    ->whereMonth('date', $thisMonth['month'])
                                    ->sum('withdrawal');

        // ── Expenses ──────────────────────────────────────────────────
        $expensesThisMonth = Expense::whereYear('date', $thisMonth['year'])
                                ->whereMonth('date', $thisMonth['month'])
                                ->sum('amount');

        // ── Top clients by total invoiced (all time) ──────────────────
        $topClients = Client::withSum('invoices as total_invoiced', 'total')
                        ->withCount('invoices')
                        ->orderByDesc('total_invoiced')
                        ->limit(5)
                        ->get();

        // ── Recent movements ──────────────────────────────────────────
        $recentMovements = BankMovement::with('bankAccount')
                            ->latest('date')
                            ->limit(6)
                            ->get();

        // ── Overdue invoices ──────────────────────────────────────────
        $overdueInvoices = Invoice::with('client')
                            ->where('status', 'pending')
                            ->where('date_due', '<', $now)
                            ->orderBy('date_due')
                            ->limit(5)
                            ->get();

        // ── Upcoming reminders ────────────────────────────────────────
        $upcomingReminders = PaymentReminder::with('remindable')
                                ->active()
                                ->orderBy('reminder_date')
                                ->limit(5)
                                ->get();

        // ── 6-month chart ─────────────────────────────────────────────
        $monthlyData = $this->getMonthlyData();

        // ── Invoice status breakdown ──────────────────────────────────
        $statusBreakdown = $this->getStatusBreakdown();

        $movementCategoryStats = $this->getMovementCategoryStats();
        $movementTypeStats = $this->getMovementTypeStats();
        $invoiceProjectStats = $this->getInvoiceProjectStats();
        $invoicePaymentTypeStats = $this->getInvoicePaymentTypeStats();

        return view('livewire.dashboard.dashboard-page', compact(
            'totalReceivable', 'totalCollected', 'overdueCount', 'overdueAmount',
            'pendingCount', 'collectionRate',
            'bankAccounts', 'totalBankBalance',
            'depositsThisMonth', 'withdrawalsThisMonth',
            'expensesThisMonth',
            'topClients', 'recentMovements',
            'overdueInvoices', 'upcomingReminders',
            'monthlyData', 'statusBreakdown',
            'movementCategoryStats', 'movementTypeStats',
            'invoiceProjectStats', 'invoicePaymentTypeStats',
        ));
    }

    public function quickMarkPaid(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $invoice->update(['status' => 'paid', 'amount_paid' => $invoice->total, 'amount_remaining' => 0]);
        ActivityLog::log('invoice_paid', "Invoice #{$invoice->invoice_number} marked as paid", $invoice);
    }

    private function getMonthlyData(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $income   = Invoice::where('status', 'paid')
                            ->whereYear('date_issued', $date->year)
                            ->whereMonth('date_issued', $date->month)
                            ->sum('total');
            $expenses = Expense::whereYear('date', $date->year)
                            ->whereMonth('date', $date->month)
                            ->sum('amount');
            $deposits    = BankMovement::whereYear('date', $date->year)->whereMonth('date', $date->month)->sum('deposit');
            $withdrawals = BankMovement::whereYear('date', $date->year)->whereMonth('date', $date->month)->sum('withdrawal');
            $months->push([
                'label'       => $date->format('M y'),
                'income'      => (float) $income,
                'expenses'    => (float) $expenses,
                'deposits'    => (float) $deposits,
                'withdrawals' => (float) $withdrawals,
            ]);
        }
        return $months->toArray();
    }

    private function getStatusBreakdown(): array
    {
        $statuses = ['pending', 'paid', 'partial', 'overdue', 'cancelled'];
        $data = [];
        foreach ($statuses as $status) {
            $count = $status === 'overdue'
                ? Invoice::where('status', 'pending')->where('date_due', '<', now())->count()
                : Invoice::where('status', $status)->count();
            if ($count > 0) {
                $data[] = ['status' => $status, 'count' => $count];
            }
        }
        return $data;
    }

    private function getMovementCategoryStats(): array
    {
        $rows = BankMovement::query()
            ->select(
                'category',
                DB::raw('COUNT(*) as movement_count'),
                DB::raw('COALESCE(SUM(deposit), 0) as deposits'),
                DB::raw('COALESCE(SUM(withdrawal), 0) as withdrawals')
            )
            ->groupBy('category')
            ->get();

        return $rows
            ->map(function ($r) {
                $net = (float) $r->deposits - (float) $r->withdrawals;
                return [
                    'key' => $r->category,
                    'name' => $r->category ?: __('app.none'),
                    'count' => (int) $r->movement_count,
                    'deposits' => (float) $r->deposits,
                    'withdrawals' => (float) $r->withdrawals,
                    'net' => $net,
                ];
            })
            ->sortByDesc(fn ($x) => abs($x['net']))
            ->values()
            ->take(5)
            ->all();
    }

    private function getMovementTypeStats(): array
    {
        $typeMap = MovementType::query()->get()->keyBy('slug')->map(fn ($mt) => $mt->name)->all();

        $rows = BankMovement::query()
            ->select(
                'type',
                DB::raw('COUNT(*) as movement_count'),
                DB::raw('COALESCE(SUM(deposit), 0) as deposits'),
                DB::raw('COALESCE(SUM(withdrawal), 0) as withdrawals')
            )
            ->groupBy('type')
            ->get();

        return $rows
            ->map(function ($r) use ($typeMap) {
                $typeKey = $r->type;
                $net = (float) $r->deposits - (float) $r->withdrawals;
                $label = $typeKey ? ($typeMap[$typeKey] ?? $typeKey) : __('app.none');

                return [
                    'key' => $typeKey,
                    'name' => $label,
                    'count' => (int) $r->movement_count,
                    'deposits' => (float) $r->deposits,
                    'withdrawals' => (float) $r->withdrawals,
                    'net' => $net,
                ];
            })
            ->sortByDesc(fn ($x) => abs($x['net']))
            ->values()
            ->take(5)
            ->all();
    }

    private function getInvoiceProjectStats(): array
    {
        $rows = Invoice::query()
            ->leftJoin('projects', 'invoices.project_id', '=', 'projects.id')
            ->select(
                'projects.name as project_name',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('COALESCE(SUM(invoices.total), 0) as total'),
                DB::raw('COALESCE(SUM(invoices.amount_remaining), 0) as remaining')
            )
            ->groupBy('projects.id', 'projects.name')
            ->get();

        return $rows
            ->map(function ($r) {
                return [
                    'key' => $r->project_name,
                    'name' => $r->project_name ?: __('app.none'),
                    'count' => (int) $r->invoice_count,
                    'total' => (float) $r->total,
                    'remaining' => (float) $r->remaining,
                ];
            })
            ->sortByDesc(fn ($x) => $x['total'])
            ->values()
            ->take(5)
            ->all();
    }

    private function getInvoicePaymentTypeStats(): array
    {
        $rows = Invoice::query()
            ->select(
                'payment_type',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('COALESCE(SUM(invoices.total), 0) as total'),
                DB::raw('COALESCE(SUM(invoices.amount_remaining), 0) as remaining')
            )
            ->groupBy('payment_type')
            ->get();

        return $rows
            ->map(function ($r) {
                $key = $r->payment_type;
                if (! $key) {
                    $label = __('app.none');
                } else {
                    $translated = __('app.' . $key);
                    $label = $translated === ('app.' . $key) ? $key : $translated;
                }

                return [
                    'key' => $key,
                    'name' => $label,
                    'count' => (int) $r->invoice_count,
                    'total' => (float) $r->total,
                    'remaining' => (float) $r->remaining,
                ];
            })
            ->sortByDesc(fn ($x) => $x['remaining'])
            ->values()
            ->take(5)
            ->all();
    }
}
