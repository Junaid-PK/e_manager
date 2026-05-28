<?php

namespace App\Livewire\WorkerPayments;

use App\Exports\WorkerPaymentExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\MonthlyPeriod;
use App\Models\ProjectMonth;
use App\Models\Worker;
use App\Models\WorkerPayment;
use App\Services\WorkerPaymentImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class WorkerPaymentPage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting, WithFileUploads;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showImportModal = false;
    public ?int $editingId = null;

    public string $filterPeriodId = '';
    public string $filterWorkerId = '';
    public string $filterPaymentType = '';
    public string $filterProjectMonthId = '';

    public string $formWorkerId = '';
    public string $formPeriodId = '';
    public string $formProjectMonthId = '';
    public string $formPaymentDate = '';
    public string $formPaymentType = 'bank';
    public string $formAmount = '0';
    public string $formReference = '';
    public string $formNotes = '';

    public $importFile = null;
    public array $importPreview = [];
    public array $importColumnMap = [];
    public int $importStep = 1;

    protected function rules(): array
    {
        return [
            'formWorkerId' => 'required|exists:workers,id',
            'formPeriodId' => 'required|exists:monthly_periods,id',
            'formProjectMonthId' => 'required|exists:project_months,id',
            'formPaymentDate' => 'required|date',
            'formPaymentType' => 'required|in:bank,cash,advance,ticket,adjustment',
            'formAmount' => 'required|numeric|min:0',
            'formReference' => 'nullable|string|max:255',
            'formNotes' => 'nullable|string',
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

    public function updatedFilterPaymentType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProjectMonthId(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->formPaymentDate = now()->format('Y-m-d');
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $row = WorkerPayment::findOrFail($id);
        $this->editingId = $id;
        $this->formWorkerId = (string) $row->worker_id;
        $this->formPeriodId = (string) $row->monthly_period_id;
        $this->formProjectMonthId = (string) $row->project_month_id;
        $this->formPaymentDate = $row->payment_date?->format('Y-m-d') ?? '';
        $this->formPaymentType = $row->payment_type;
        $this->formAmount = (string) $row->amount;
        $this->formReference = $row->reference ?? '';
        $this->formNotes = $row->notes ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('worker_payments.edit');
        } else {
            Gate::authorize('worker_payments.create');
        }

        $this->validate();

        $data = [
            'worker_id' => (int) $this->formWorkerId,
            'monthly_period_id' => (int) $this->formPeriodId,
            'project_month_id' => (int) $this->formProjectMonthId,
            'payment_date' => $this->formPaymentDate,
            'payment_type' => $this->formPaymentType,
            'amount' => (float) ($this->formAmount ?: 0),
            'reference' => $this->formReference ?: null,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->editingId) {
            WorkerPayment::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            WorkerPayment::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function quickUpdateField(int $id, string $field, string $value): void
    {
        Gate::authorize('worker_payments.edit');

        $row = WorkerPayment::findOrFail($id);

        $allowedFields = ['amount'];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        $numericValue = (float) str_replace(',', '.', str_replace('.', '', $value));
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
        Gate::authorize('worker_payments.delete');
        if ($this->editingId) {
            WorkerPayment::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('worker_payments.delete');
        WorkerPayment::whereIn('id', $this->selected)->delete();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        $this->deselectAll();
    }

    public function exportToExcel()
    {
        Gate::authorize('worker_payments.export');
        $rows = $this->buildQuery()->get();
        $filename = 'worker-payments-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new WorkerPaymentExport($rows), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function openImportModal(): void
    {
        Gate::authorize('worker_payments.create');
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
        $service = new WorkerPaymentImportService();
        $result = $service->parseFile($path);

        $this->importPreview = $result;

        $headers = $result['headers'] ?? [];
        $map = [];
        foreach ($headers as $idx => $header) {
            $lower = mb_strtolower(trim($header));
            if (str_contains($lower, 'period') || str_contains($lower, 'code') || str_contains($lower, 'periodo') || str_contains($lower, 'código')) {
                $map['period_code'] = $idx;
            } elseif (str_contains($lower, 'worker') || str_contains($lower, 'name') || str_contains($lower, 'nombre') || str_contains($lower, 'trabajador')) {
                $map['worker'] = $idx;
            } elseif (str_contains($lower, 'project') || str_contains($lower, 'proyecto') || str_contains($lower, 'sheet') || str_contains($lower, 'hoja')) {
                $map['project_month'] = $idx;
            } elseif (str_contains($lower, 'date') || str_contains($lower, 'fecha') || str_contains($lower, 'payment date') || str_contains($lower, 'fecha pago')) {
                $map['payment_date'] = $idx;
            } elseif (str_contains($lower, 'type') || str_contains($lower, 'tipo') || str_contains($lower, 'method') || str_contains($lower, 'método') || str_contains($lower, 'metodo')) {
                $map['payment_type'] = $idx;
            } elseif (str_contains($lower, 'amount') || str_contains($lower, 'importe') || str_contains($lower, 'cantidad')) {
                $map['amount'] = $idx;
            } elseif (str_contains($lower, 'reference') || str_contains($lower, 'referencia') || str_contains($lower, 'ref')) {
                $map['reference'] = $idx;
            } elseif (str_contains($lower, 'notes') || str_contains($lower, 'notas') || str_contains($lower, 'note') || str_contains($lower, 'nota') || str_contains($lower, 'observation') || str_contains($lower, 'observación')) {
                $map['notes'] = $idx;
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importRows(): void
    {
        Gate::authorize('worker_payments.create');
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new WorkerPaymentImportService();
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
        $this->formWorkerId = '';
        $this->formPeriodId = '';
        $this->formProjectMonthId = '';
        $this->formPaymentDate = '';
        $this->formPaymentType = 'bank';
        $this->formAmount = '0';
        $this->formReference = '';
        $this->formNotes = '';
        $this->resetValidation();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterPeriodId = '';
        $this->filterWorkerId = '';
        $this->filterPaymentType = '';
        $this->filterProjectMonthId = '';
        $this->resetPage();
    }

    public function render()
    {
        $this->ensurePeriodsExist();

        $query = $this->buildQuery();
        $rows = $query->paginate($this->perPage);
        $allRows = $query->get();

        $totals = [
            'amount' => $allRows->sum('amount'),
        ];

        return view('livewire.worker-payments.worker-payment-page', [
            'rows' => $rows,
            'totals' => $totals,
            'periods' => MonthlyPeriod::orderByDesc('year')->orderByDesc('month')->get(),
            'workers' => Worker::orderBy('full_name')->get(),
            'projectMonths' => ProjectMonth::with(['monthlyPeriod', 'client', 'project'])->orderByDesc('id')->get(),
            'paymentTypes' => [
                'bank' => __('app.bank'),
                'cash' => __('app.cash'),
                'advance' => __('app.advance'),
                'ticket' => __('app.ticket'),
                'adjustment' => __('app.adjustment'),
            ],
        ])->layout('layouts.app');
    }

    protected function ensurePeriodsExist(): void
    {
        $currentYear = (int) now()->format('Y');
        $existingCount = \App\Models\MonthlyPeriod::where('year', $currentYear)->count();

        if ($existingCount < 12) {
            for ($month = 1; $month <= 12; $month++) {
                \App\Models\MonthlyPeriod::firstOrCreateForMonth($currentYear, $month);
            }
        }
    }

    protected function buildQuery()
    {
        $query = WorkerPayment::query()
            ->with(['worker', 'monthlyPeriod', 'projectMonth.client', 'projectMonth.project']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('worker', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"))
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('monthlyPeriod', fn ($sq) => $sq->where('period_code', 'like', "%{$search}%"));
            });
        }

        if ($this->filterPeriodId) {
            $query->where('monthly_period_id', (int) $this->filterPeriodId);
        }

        if ($this->filterWorkerId) {
            $query->where('worker_id', (int) $this->filterWorkerId);
        }

        if ($this->filterPaymentType) {
            $query->where('payment_type', $this->filterPaymentType);
        }

        if ($this->filterProjectMonthId) {
            $query->where('project_month_id', (int) $this->filterProjectMonthId);
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
