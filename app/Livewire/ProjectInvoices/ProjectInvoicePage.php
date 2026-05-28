<?php

namespace App\Livewire\ProjectInvoices;

use App\Exports\ProjectInvoiceExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Client;
use App\Models\MonthlyPeriod;
use App\Models\Project;
use App\Models\ProjectInvoice;
use App\Models\ProjectMonth;
use App\Services\ProjectInvoiceImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ProjectInvoicePage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting, WithFileUploads;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showImportModal = false;
    public ?int $editingId = null;

    public string $filterPeriodId = '';
    public string $filterClientId = '';
    public string $filterProjectId = '';
    public string $filterStatus = '';

    public string $formProjectMonthId = '';
    public string $formInvoiceNo = '';
    public string $formInvoiceDate = '';
    public string $formEstimatedAmount = '0';
    public string $formActualAmount = '0';
    public string $formStatus = 'draft';
    public string $formNotes = '';

    public $importFile = null;
    public array $importPreview = [];
    public array $importColumnMap = [];
    public int $importStep = 1;

    protected function rules(): array
    {
        return [
            'formProjectMonthId' => 'required|exists:project_months,id',
            'formInvoiceNo' => 'nullable|string|max:100',
            'formInvoiceDate' => 'nullable|date',
            'formEstimatedAmount' => 'nullable|numeric|min:0',
            'formActualAmount' => 'nullable|numeric|min:0',
            'formStatus' => 'required|in:draft,sent,paid,partial,cancelled',
            'formNotes' => 'nullable|string|max:1000',
        ];
    }

    public function updatedFilterPeriodId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClientId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProjectId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
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
        $row = ProjectInvoice::findOrFail($id);
        $this->editingId = $id;
        $this->formProjectMonthId = (string) $row->project_month_id;
        $this->formInvoiceNo = $row->invoice_no ?? '';
        $this->formInvoiceDate = $row->invoice_date ? $row->invoice_date->format('Y-m-d') : '';
        $this->formEstimatedAmount = (string) $row->estimated_amount;
        $this->formActualAmount = (string) $row->actual_amount;
        $this->formStatus = $row->status;
        $this->formNotes = $row->notes ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('project_invoices.edit');
        } else {
            Gate::authorize('project_invoices.create');
        }

        $this->validate();

        $data = [
            'project_month_id' => (int) $this->formProjectMonthId,
            'invoice_no' => $this->formInvoiceNo ?: null,
            'invoice_date' => $this->formInvoiceDate ?: null,
            'estimated_amount' => (float) ($this->formEstimatedAmount ?: 0),
            'actual_amount' => (float) ($this->formActualAmount ?: 0),
            'status' => $this->formStatus,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->editingId) {
            ProjectInvoice::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            ProjectInvoice::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function quickUpdateField(int $id, string $field, string $value): void
    {
        Gate::authorize('project_invoices.edit');

        $row = ProjectInvoice::findOrFail($id);

        $allowedFields = ['invoice_no', 'estimated_amount', 'actual_amount', 'notes'];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        if (in_array($field, ['estimated_amount', 'actual_amount'], true)) {
            $value = (float) str_replace(',', '.', str_replace('.', '', $value));
        }

        $row->{$field} = $value ?: null;
        $row->save();
    }

    public function quickUpdateStatus(int $id, string $status): void
    {
        Gate::authorize('project_invoices.edit');

        $row = ProjectInvoice::findOrFail($id);
        $row->status = $status;
        $row->save();
    }

    public function confirmDelete(int $id): void
    {
        $this->editingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Gate::authorize('project_invoices.delete');
        if ($this->editingId) {
            ProjectInvoice::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('project_invoices.delete');
        ProjectInvoice::whereIn('id', $this->selected)->delete();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        $this->deselectAll();
    }

    public function exportToExcel()
    {
        Gate::authorize('project_invoices.export');
        $rows = $this->buildQuery()->get();
        $filename = 'project-invoices-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new ProjectInvoiceExport($rows), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function openImportModal(): void
    {
        Gate::authorize('project_invoices.create');
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
        $service = new ProjectInvoiceImportService();
        $result = $service->parseFile($path);

        $this->importPreview = $result;

        $headers = $result['headers'] ?? [];
        $map = [];
        foreach ($headers as $idx => $header) {
            $lower = mb_strtolower(trim($header));
            if (str_contains($lower, 'period') || str_contains($lower, 'code')) {
                $map['period_code'] = $idx;
            } elseif (str_contains($lower, 'client')) {
                $map['client'] = $idx;
            } elseif (str_contains($lower, 'project')) {
                $map['project'] = $idx;
            } elseif (str_contains($lower, 'invoice') && (str_contains($lower, 'no') || str_contains($lower, 'num') || str_contains($lower, '#'))) {
                $map['invoice_no'] = $idx;
            } elseif (str_contains($lower, 'date') || str_contains($lower, 'fecha')) {
                $map['invoice_date'] = $idx;
            } elseif (str_contains($lower, 'estimated') || str_contains($lower, 'estimado') || str_contains($lower, 'previsto')) {
                $map['estimated_amount'] = $idx;
            } elseif (str_contains($lower, 'actual') || str_contains($lower, 'real') || str_contains($lower, 'amount')) {
                $map['actual_amount'] = $idx;
            } elseif (str_contains($lower, 'status') || str_contains($lower, 'estado')) {
                $map['status'] = $idx;
            } elseif (str_contains($lower, 'notes') || str_contains($lower, 'notas') || str_contains($lower, 'observ')) {
                $map['notes'] = $idx;
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importRows(): void
    {
        Gate::authorize('project_invoices.create');
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new ProjectInvoiceImportService();
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
        $this->formProjectMonthId = '';
        $this->formInvoiceNo = '';
        $this->formInvoiceDate = '';
        $this->formEstimatedAmount = '0';
        $this->formActualAmount = '0';
        $this->formStatus = 'draft';
        $this->formNotes = '';
        $this->resetValidation();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterPeriodId = '';
        $this->filterClientId = '';
        $this->filterProjectId = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->buildQuery();
        $rows = $query->paginate($this->perPage);
        $allRows = $query->get();

        $totals = [
            'estimated_amount' => $allRows->sum('estimated_amount'),
            'actual_amount' => $allRows->sum('actual_amount'),
            'difference' => $allRows->sum(fn ($r) => (float) $r->actual_amount - (float) $r->estimated_amount),
        ];

        return view('livewire.project-invoices.project-invoice-page', [
            'rows' => $rows,
            'totals' => $totals,
            'periods' => MonthlyPeriod::orderByDesc('year')->orderByDesc('month')->get(),
            'clients' => Client::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'projectMonths' => ProjectMonth::with(['monthlyPeriod', 'client', 'project'])->orderByDesc('id')->get(),
        ])->layout('layouts.app');
    }

    protected function buildQuery()
    {
        $query = ProjectInvoice::query()
            ->with(['projectMonth.monthlyPeriod', 'projectMonth.client', 'projectMonth.project']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('projectMonth.monthlyPeriod', fn ($sq) => $sq->where('period_code', 'like', "%{$search}%"))
                    ->orWhereHas('projectMonth.client', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('projectMonth.project', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($this->filterPeriodId) {
            $query->whereHas('projectMonth', fn ($q) => $q->where('monthly_period_id', (int) $this->filterPeriodId));
        }

        if ($this->filterClientId) {
            $query->whereHas('projectMonth', fn ($q) => $q->where('client_id', (int) $this->filterClientId));
        }

        if ($this->filterProjectId) {
            $query->whereHas('projectMonth', fn ($q) => $q->where('project_id', (int) $this->filterProjectId));
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
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
