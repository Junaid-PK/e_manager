<?php

namespace App\Livewire\ProjectMonths;

use App\Exports\ProjectMonthExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Client;
use App\Models\MonthlyPeriod;
use App\Models\Project;
use App\Models\ProjectMonth;
use App\Services\ProjectMonthImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ProjectMonthPage extends Component
{
    use WithBulkActions, WithFileUploads, WithFiltering, WithPagination, WithSorting;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showImportModal = false;

    public ?int $editingId = null;

    public string $filterPeriodId = '';

    public string $filterClientId = '';

    public string $filterProjectId = '';

    public string $formPeriodId = '';

    public string $formClientId = '';

    public string $formProjectId = '';

    public string $formSheetCode = '';

    public string $formEstimatedInvoice = '0';

    public string $formTotalExpenses = '0';

    public string $formTotalInvoiced = '0';

    public $importFile = null;

    public array $importPreview = [];

    public array $importColumnMap = [];

    public int $importStep = 1;

    protected function rules(): array
    {
        return [
            'formPeriodId' => 'required|exists:monthly_periods,id',
            'formClientId' => 'required|exists:clients,id',
            'formProjectId' => 'required|exists:projects,id',
            'formSheetCode' => 'nullable|string|max:50',
            'formEstimatedInvoice' => 'nullable|numeric|min:0',
            'formTotalExpenses' => 'nullable|numeric|min:0',
            'formTotalInvoiced' => 'nullable|numeric|min:0',
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

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $row = ProjectMonth::findOrFail($id);
        $this->editingId = $id;
        $this->formPeriodId = (string) $row->monthly_period_id;
        $this->formClientId = (string) $row->client_id;
        $this->formProjectId = (string) $row->project_id;
        $this->formSheetCode = $row->sheet_code ?? '';
        $this->formEstimatedInvoice = (string) $row->estimated_invoice;
        $this->formTotalExpenses = (string) $row->total_expenses;
        $this->formTotalInvoiced = (string) $row->total_invoiced;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('project_months.edit');
        } else {
            Gate::authorize('project_months.create');
        }

        $this->validate();

        $data = [
            'monthly_period_id' => (int) $this->formPeriodId,
            'client_id' => (int) $this->formClientId,
            'project_id' => (int) $this->formProjectId,
            'sheet_code' => $this->formSheetCode ?: null,
            'estimated_invoice' => (float) ($this->formEstimatedInvoice ?: 0),
            'total_expenses' => (float) ($this->formTotalExpenses ?: 0),
            'total_invoiced' => (float) ($this->formTotalInvoiced ?: 0),
        ];

        if ($this->editingId) {
            ProjectMonth::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            ProjectMonth::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function quickUpdateField(int $id, string $field, string $value): void
    {
        Gate::authorize('project_months.edit');

        $row = ProjectMonth::findOrFail($id);
        $numericValue = (float) str_replace(',', '.', str_replace('.', '', $value));

        $allowedFields = [
            'sheet_code',
            'estimated_invoice',
            'total_expenses',
            'total_invoiced',
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
        Gate::authorize('project_months.delete');
        if ($this->editingId) {
            ProjectMonth::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('project_months.delete');
        ProjectMonth::whereIn('id', $this->selected)->delete();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        $this->deselectAll();
    }

    public function exportToExcel()
    {
        Gate::authorize('project_months.export');
        $rows = $this->buildQuery()->get();
        $filename = 'project-months-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new ProjectMonthExport($rows), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function openImportModal(): void
    {
        Gate::authorize('project_months.create');
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;
        $this->showImportModal = true;
    }

    public function updatedImportFile(): void
    {
        if (! $this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new ProjectMonthImportService;
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
            } elseif (str_contains($lower, 'sheet')) {
                $map['sheet_code'] = $idx;
            } elseif (str_contains($lower, 'nominal')) {
                $map['total_nominal'] = $idx;
            } elseif (str_contains($lower, 'social') || str_contains($lower, 'ss') || str_contains($lower, 'seguridad')) {
                $map['total_social_security'] = $idx;
            } elseif (str_contains($lower, 'expense') || str_contains($lower, 'gasto')) {
                $map['total_expenses'] = $idx;
            } elseif (str_contains($lower, 'invoiced') || str_contains($lower, 'facturado')) {
                $map['total_invoiced'] = $idx;
            } elseif (str_contains($lower, 'estimated') || str_contains($lower, 'estimado') || str_contains($lower, 'previsto')) {
                $map['estimated_invoice'] = $idx;
            } elseif (str_contains($lower, 'difference') || str_contains($lower, 'diferencia')) {
                $map['difference'] = $idx;
            } elseif (str_contains($lower, 'hours') || str_contains($lower, 'horas')) {
                $map['total_hours'] = $idx;
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importRows(): void
    {
        Gate::authorize('project_months.create');
        if (! $this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new ProjectMonthImportService;
        $result = $service->importMappedData($path, $this->importColumnMap);

        $this->showImportModal = false;
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;

        if ($result['imported'] > 0) {
            $this->dispatch('notify', type: 'success', message: $result['imported'].' '.__('app.rows_imported'));
        }

        if (! empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->dispatch('notify', type: 'error', message: $error);
            }
        }
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->formPeriodId = '';
        $this->formClientId = '';
        $this->formProjectId = '';
        $this->formSheetCode = '';
        $this->formEstimatedInvoice = '0';
        $this->formTotalExpenses = '0';
        $this->formTotalInvoiced = '0';
        $this->resetValidation();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterPeriodId = '';
        $this->filterClientId = '';
        $this->filterProjectId = '';
        $this->resetPage();
    }

    public function render()
    {
        $this->ensurePeriodsExist();

        $query = $this->buildQuery();
        $rows = $query->paginate($this->perPage);
        $allRows = $query->get();

        $totals = [
            'total_nominal' => $allRows->sum('total_nominal'),
            'total_social_security' => $allRows->sum('total_social_security'),
            'total_expenses' => $allRows->sum('total_expenses'),
            'total_invoiced' => $allRows->sum('total_invoiced'),
            'estimated_invoice' => $allRows->sum('estimated_invoice'),
            'difference' => $allRows->sum('difference'),
            'total_hours' => $allRows->sum('total_hours'),
        ];

        return view('livewire.project-months.project-month-page', [
            'rows' => $rows,
            'totals' => $totals,
            'periods' => MonthlyPeriod::orderByDesc('year')->orderByDesc('month')->get(),
            'clients' => Client::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
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
        $query = ProjectMonth::query()
            ->with(['monthlyPeriod', 'client', 'project']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('sheet_code', 'like', "%{$search}%")
                    ->orWhereHas('monthlyPeriod', fn ($sq) => $sq->where('period_code', 'like', "%{$search}%"))
                    ->orWhereHas('client', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('project', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($this->filterPeriodId) {
            $query->where('monthly_period_id', (int) $this->filterPeriodId);
        }

        if ($this->filterClientId) {
            $query->where('client_id', (int) $this->filterClientId);
        }

        if ($this->filterProjectId) {
            $query->where('project_id', (int) $this->filterProjectId);
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
