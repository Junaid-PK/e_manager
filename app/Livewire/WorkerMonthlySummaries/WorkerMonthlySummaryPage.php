<?php

namespace App\Livewire\WorkerMonthlySummaries;

use App\Exports\WorkerMonthlySummaryExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\MonthlyPeriod;
use App\Models\Worker;
use App\Models\WorkerMonthlySummary;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class WorkerMonthlySummaryPage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting;

    public string $filterPeriodId = '';
    public string $filterWorkerId = '';

    public function updatedFilterPeriodId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterWorkerId(): void
    {
        $this->resetPage();
    }

    public function exportToExcel()
    {
        Gate::authorize('worker_monthly_summaries.export');
        $rows = $this->buildQuery()->get();
        $filename = 'worker-monthly-summaries-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new WorkerMonthlySummaryExport($rows), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterPeriodId = '';
        $this->filterWorkerId = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->buildQuery();
        $rows = $query->paginate($this->perPage);
        $allRows = $query->get();

        $totals = [
            'total_amount' => $allRows->sum('total_amount'),
            'paid_amount' => $allRows->sum(fn ($row) => $row->paid_amount),
            'total_hours' => $allRows->sum('total_hours'),
            'payroll_amount' => $allRows->sum('payroll_amount'),
            'advance_amount' => $allRows->sum('advance_amount'),
            'credit_amount' => $allRows->sum('credit_amount'),
            'ticket_amount' => $allRows->sum('ticket_amount'),
            'difference' => $allRows->sum(fn ($row) => $row->difference),
            'final_difference' => $allRows->sum(fn ($row) => $row->final_difference),
        ];

        return view('livewire.worker-monthly-summaries.worker-monthly-summary-page', [
            'rows' => $rows,
            'totals' => $totals,
            'periods' => MonthlyPeriod::orderByDesc('year')->orderByDesc('month')->get(),
            'workers' => Worker::orderBy('full_name')->get(),
        ])->layout('layouts.app');
    }

    protected function buildQuery()
    {
        $query = WorkerMonthlySummary::query()
            ->with(['monthlyPeriod', 'worker']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('monthlyPeriod', fn ($sq) => $sq->where('period_code', 'like', "%{$search}%"))
                    ->orWhereHas('worker', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($this->filterPeriodId) {
            $query->where('monthly_period_id', (int) $this->filterPeriodId);
        }

        if ($this->filterWorkerId) {
            $query->where('worker_id', (int) $this->filterWorkerId);
        }

        return $this->applySorting($query);
    }

    protected function getPageItemIds(): array
    {
        return $this->buildQuery()->paginate($this->perPage)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->buildQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }
}
