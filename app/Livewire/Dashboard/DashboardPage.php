<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentReminder;
use Livewire\Component;

class DashboardPage extends Component
{
    public string $chartPeriod = 'month';

    public function render()
    {
        return view('livewire.dashboard.dashboard-page', [
            'totalReceivable' => Invoice::whereIn('status', ['pending', 'partial'])->sum('total'),
            'totalPayable' => Expense::whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount'),
            'overdueCount' => Invoice::where('status', 'pending')->where('date_due', '<', now())->count(),
            'totalBankBalance' => BankAccount::sum('current_balance'),
            'overdueInvoices' => Invoice::with('client')
                ->where('status', 'pending')
                ->where('date_due', '<', now())
                ->orderBy('date_due')
                ->limit(5)
                ->get(),
            'recentActivity' => ActivityLog::with('user')
                ->latest()
                ->limit(10)
                ->get(),
            'upcomingReminders' => PaymentReminder::with('remindable')
                ->active()
                ->orderBy('reminder_date')
                ->limit(5)
                ->get(),
            'monthlyData' => $this->getMonthlyData(),
            'statusBreakdown' => $this->getStatusBreakdown(),
        ])->layout('layouts.app');
    }

    public function quickMarkPaid(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $invoice->update(['status' => 'paid']);
        ActivityLog::log('invoice_paid', "Invoice #{$invoice->invoice_number} marked as paid", $invoice);
    }

    private function getMonthlyData(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthLabel = $date->format('M Y');

            $income = Invoice::where('status', 'paid')
                ->whereYear('date_issued', $date->year)
                ->whereMonth('date_issued', $date->month)
                ->sum('total');

            $expenses = Expense::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->sum('amount');

            $months->push([
                'label' => $monthLabel,
                'income' => (float) $income,
                'expenses' => (float) $expenses,
            ]);
        }
        return $months->toArray();
    }

    private function getStatusBreakdown(): array
    {
        $statuses = ['pending', 'paid', 'partial', 'overdue', 'cancelled'];
        $data = [];
        foreach ($statuses as $status) {
            if ($status === 'overdue') {
                $count = Invoice::where('status', 'pending')->where('date_due', '<', now())->count();
            } else {
                $count = Invoice::where('status', $status)->count();
            }
            if ($count > 0) {
                $data[] = ['status' => $status, 'count' => $count];
            }
        }
        return $data;
    }
}
