<?php

namespace App\Livewire\WorkerMonthlySummaries;

use App\Exports\WorkerMonthlySummaryExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\MonthlyPeriod;
use App\Models\Worker;
use App\Models\WorkerMonthlySummary;
use App\Services\WorkerMonthlySummaryImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class WorkerMonthlySummaryPage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting, WithFileUploads;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showImportModal = false;
    public ?int $editingId = null;

    public string $filterPeriodId = '';
    public string $filterWorkerId = '';

    public string $formPeriodId = '';
    public string $formWorkerId = '';
    public string $formTotalAmount = '0';
    public string $formPaidAmount = '0';
    public string $formTotalHours = '0';
    public string $formPayrollAmount = '0';
    public string $formAdvanceAmount = '0';
    public string $formCreditAmount = '0';
    public string $formTicketAmount = '0';

    public $importFile = null;
    public array $importPreview = [];
    public array $importColumnMap = [];
    public int $importStep = 1;

    protected function rules(): array
    {
        return [
            'formPeriodId' => 'required|exists:monthly_periods,id',
            'formWorkerId' => 'required|exists:workers,id',
            'formTotalAmount' => 'nullable|numeric|min:0',
            'formPaidAmount' => 'nullable|numeric|min:0',
            'formTotalHours' => 'nullable|numeric|min:0',
            'formPayrollAmount' => 'nullable|numeric|min:0',
            'formAdvanceAmount' => 'nullable|numeric|min:0',
            'formCreditAmount' => 'nullable|numeric|min:0',
            'formTicketAmount' => 'nullable|numeric|min:0',
        ];
    }

    public function updatedFilterPeriodId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterWorkerId(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $row = WorkerMonthlySummary::findOrFail($id);
        $this->editingId = $id;
        $this->formPeriodId = (string) $row->monthly_period_id;
        $this->formWorkerId = (string) $row->worker_id;
        $this->formTotalAmount = (string) $row->total_amount;
        $this->formPaidAmount = (string) $row->paid_amount;
        $this->formTotalHours = (string) $row->total_hours;
        $this->formPayrollAmount = (string) $row->payroll_amount;
        $this->formAdvanceAmount = (string) $row->advance_amount;
        $this->formCreditAmount = (string) $row->credit_amount;
        $this->formTicketAmount = (string) $row->ticket_amount;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('worker_monthly_summaries.edit');
        } else {
            Gate::authorize('worker_monthly_summaries.create');
        }

        $this->validate();

        $data = [
            'monthly_period_id' => (int) $this->formPeriodId,
            'worker_id' => (int) $this->formWorkerId,
            'total_amount' => (float) ($this->formTotalAmount ?: 0),
            'paid_amount' => (float) ($this->formPaidAmount ?: 0),
            'total_hours' => (float) ($this->formTotalHours ?: 0),
            'payroll_amount' => (float) ($this->formPayrollAmount ?: 0),
            'advance_amount' => (float) ($this->formAdvanceAmount ?: 0),
            'credit_amount' => (float) ($this->formCreditAmount ?: 0),
            'ticket_amount' => (float) ($this->formTicketAmount ?: 0),
        ];

        if ($this->editingId) {
            WorkerMonthlySummary::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            WorkerMonthlySummary::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function quickUpdateField(int $id, string $field, string $value): void
    {
        Gate::authorize('worker_monthly_summaries.edit');

        $row = WorkerMonthlySummary::findOrFail($id);
        $numericValue = (float) str_replace(',', '.', str_replace('.', '', $value));

        $allowedFields = [
            'total_amount',
            'paid_amount',
            'total_hours',
            'payroll_amount',
            'advance_amount',
            'credit_amount',
            'ticket_amount',
        ];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        $row->{$field} = $numericValue;
        $row->save();
    }

    public function confirmDelete(int $id): void
    {
        $this->editingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Gate::authorize('worker_monthly_summaries.delete');
        if ($this->editingId) {
            WorkerMonthlySummary::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('worker_monthly_summaries.delete');
        WorkerMonthlySummary::whereIn('id', $this->selected)->delete();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        $this->deselectAll();
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

    public function openImportModal(): void
    {
        Gate::authorize('worker_monthly_summaries.create');
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;
        $this->showImportModal = true;
    }

    public function updatedImportFile(): void
    {
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new WorkerMonthlySummaryImportService();
        $result = $service->parseFile($path);

        $this->importPreview = $result;

        $headers = $result['headers'] ?? [];
        $map = [];
        foreach ($headers as $idx => $header) {
            $lower = mb_strtolower(trim($header));
            if (str_contains($lower, 'period') || str_contains($lower, 'code')) {
                $map['period_code'] = $idx;
            } elseif (str_contains($lower, 'worker') || str_contains($lower, 'name') || str_contains($lower, 'nombre') || str_contains($lower, 'trabajador')) {
                $map['worker'] = $idx;
            } elseif (str_contains($lower, 'total') && (str_contains($lower, 'amount') || str_contains($lower, 'importe'))) {
                $map['total_amount'] = $idx;
            } elseif (str_contains($lower, 'paid') || str_contains($lower, 'pagado') || str_contains($lower, 'cobrado')) {
                $map['paid_amount'] = $idx;
            } elseif (str_contains($lower, 'hours') || str_contains($lower, 'horas')) {
                $map['total_hours'] = $idx;
            } elseif (str_contains($lower, 'payroll') || str_contains($lower, 'nomina') || str_contains($lower, 'nómina')) {
                $map['payroll_amount'] = $idx;
            } elseif (str_contains($lower, 'advance') || str_contains($lower, 'anticipo')) {
                $map['advance_amount'] = $idx;
            } elseif (str_contains($lower, 'credit') || str_contains($lower, 'crédito')) {
                $map['credit_amount'] = $idx;
            } elseif (str_contains($lower, 'ticket') || str_contains($lower, 'vale') || str_contains($lower, 'ticket')) {
                $map['ticket_amount'] = $idx;
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importRows(): void
    {
        Gate::authorize('worker_monthly_summaries.create');
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new WorkerMonthlySummaryImportService();
        $result = $service->importMappedData($path, $this->importColumnMap);

        $this->showImportModal = false;
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;

        if ($result['imported'] > 0) {
            $this->dispatch('notify', type: 'success', message: $result['imported'].' '.__('app.rows_imported'));
        }

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->dispatch('notify', type: 'error', message: $error);
            }
        }
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->formPeriodId = '';
        $this->formWorkerId = '';
        $this->formTotalAmount = '0';
        $this->formPaidAmount = '0';
        $this->formTotalHours = '0';
        $this->formPayrollAmount = '0';
        $this->formAdvanceAmount = '0';
        $this->formCreditAmount = '0';
        $this->formTicketAmount = '0';
        $this->resetValidation();
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
            'paid_amount' => $allRows->sum('paid_amount'),
            'total_hours' => $allRows->sum('total_hours'),
            'payroll_amount' => $allRows->sum('payroll_amount'),
            'advance_amount' => $allRows->sum('advance_amount'),
            'credit_amount' => $allRows->sum('credit_amount'),
            'ticket_amount' => $allRows->sum('ticket_amount'),
            'difference' => $allRows->sum('difference'),
            'final_difference' => $allRows->sum('final_difference'),
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
