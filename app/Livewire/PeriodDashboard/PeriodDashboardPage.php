<?php

namespace App\Livewire\PeriodDashboard;

use App\Models\MonthlyPeriod;
use App\Models\ProjectMonth;
use App\Models\WorkerMonthlySummary;
use Livewire\Component;

#[\Livewire\Attributes\Layout('layouts.app')]
class PeriodDashboardPage extends Component
{
    public string $selectedPeriodId = '';

    public ?MonthlyPeriod $selectedPeriod = null;

    public function mount(): void
    {
        $this->selectedPeriodId = session('dashboard_period_id', '');
        if (! $this->selectedPeriodId) {
            $currentPeriod = MonthlyPeriod::where('year', now()->year)
                ->where('month', now()->month)
                ->first();
            if ($currentPeriod) {
                $this->selectedPeriodId = (string) $currentPeriod->id;
                session(['dashboard_period_id' => $this->selectedPeriodId]);
            }
        }
        $this->loadPeriod();
    }

    public function updatedSelectedPeriodId(): void
    {
        session(['dashboard_period_id' => $this->selectedPeriodId]);
        $this->loadPeriod();
    }

    private function ensurePeriodsExist(): void
    {
        $currentYear = (int) now()->format('Y');
        $existingCount = MonthlyPeriod::where('year', $currentYear)->count();

        if ($existingCount < 12) {
            for ($month = 1; $month <= 12; $month++) {
                MonthlyPeriod::firstOrCreateForMonth($currentYear, $month);
            }
        }
    }

    private function loadPeriod(): void
    {
        if ($this->selectedPeriodId) {
            $this->selectedPeriod = MonthlyPeriod::find($this->selectedPeriodId);
        }
    }

    public function render()
    {
        $this->ensurePeriodsExist();

        $periods = MonthlyPeriod::orderByDesc('year')->orderByDesc('month')->get();
        $workersData = $this->getWorkersData();
        $projectsData = $this->getProjectsData();

        return view('livewire.period-dashboard.period-dashboard-page', compact(
            'periods',
            'workersData',
            'projectsData',
        ));
    }

    private function getWorkersData(): array
    {
        if (! $this->selectedPeriod) {
            return ['workers' => collect(), 'totals' => []];
        }

        $workers = WorkerMonthlySummary::with('worker')
            ->where('monthly_period_id', $this->selectedPeriod->id)
            ->get()
            ->sortByDesc('total_amount');

        $totals = [
            'total_workers' => $workers->count(),
            'total_amount' => $workers->sum('total_amount'),
            'paid_amount' => $workers->sum('paid_amount'),
            'total_hours' => $workers->sum('total_hours'),
            'payroll_amount' => $workers->sum('payroll_amount'),
            'advance_amount' => $workers->sum('advance_amount'),
            'credit_amount' => $workers->sum('credit_amount'),
            'ticket_amount' => $workers->sum('ticket_amount'),
            'difference' => $workers->sum('difference'),
            'final_difference' => $workers->sum('final_difference'),
            'remaining' => $workers->sum('total_amount') - $workers->sum('paid_amount'),
        ];

        return ['workers' => $workers, 'totals' => $totals];
    }

    private function getProjectsData(): array
    {
        if (! $this->selectedPeriod) {
            return ['projects' => collect(), 'totals' => []];
        }

        $projects = ProjectMonth::with(['client', 'project'])
            ->where('monthly_period_id', $this->selectedPeriod->id)
            ->get()
            ->sortByDesc('total_invoiced');

        $totals = [
            'total_nominal' => $projects->sum('total_nominal'),
            'total_social_security' => $projects->sum('total_social_security'),
            'total_expenses' => $projects->sum('total_expenses'),
            'total_invoiced' => $projects->sum('total_invoiced'),
            'estimated_invoice' => $projects->sum('estimated_invoice'),
            'difference' => $projects->sum('difference'),
            'total_hours' => $projects->sum('total_hours'),
            'margin' => $projects->sum(function ($pm) {
                return $pm->margin;
            }),
        ];

        return ['projects' => $projects, 'totals' => $totals];
    }
}
