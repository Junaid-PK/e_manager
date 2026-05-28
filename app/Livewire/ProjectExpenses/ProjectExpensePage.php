<?php

namespace App\Livewire\ProjectExpenses;

use App\Exports\ProjectExpenseExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Client;
use App\Models\MonthlyPeriod;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectMonth;
use App\Services\ProjectExpenseImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ProjectExpensePage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting, WithFileUploads;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showImportModal = false;
    public ?int $editingId = null;

    public string $filterPeriodId = '';
    public string $filterClientId = '';
    public string $filterProjectId = '';
    public string $filterCategory = '';

    public string $formProjectMonthId = '';
    public string $formExpenseDate = '';
    public string $formCategory = '';
    public string $formDescription = '';
    public string $formAmount = '0';

    public $importFile = null;
    public array $importPreview = [];
    public array $importColumnMap = [];
    public int $importStep = 1;

    protected function rules(): array
    {
        return [
            'formProjectMonthId' => 'required|exists:project_months,id',
            'formExpenseDate' => 'nullable|date',
            'formCategory' => 'nullable|string|max:100',
            'formDescription' => 'nullable|string|max:1000',
            'formAmount' => 'required|numeric|min:0',
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

    public function updatedFilterCategory(): void
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
        $row = ProjectExpense::findOrFail($id);
        $this->editingId = $id;
        $this->formProjectMonthId = (string) $row->project_month_id;
        $this->formExpenseDate = $row->expense_date ? $row->expense_date->format('Y-m-d') : '';
        $this->formCategory = $row->category ?? '';
        $this->formDescription = $row->description ?? '';
        $this->formAmount = (string) $row->amount;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('project_expenses.edit');
        } else {
            Gate::authorize('project_expenses.create');
        }

        $this->validate();

        $data = [
            'project_month_id' => (int) $this->formProjectMonthId,
            'expense_date' => $this->formExpenseDate ?: null,
            'category' => $this->formCategory ?: null,
            'description' => $this->formDescription ?: null,
            'amount' => (float) ($this->formAmount ?: 0),
        ];

        if ($this->editingId) {
            ProjectExpense::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            ProjectExpense::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function quickUpdateField(int $id, string $field, string $value): void
    {
        Gate::authorize('project_expenses.edit');

        $row = ProjectExpense::findOrFail($id);

        $allowedFields = ['category', 'description', 'amount'];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        if ($field === 'amount') {
            $value = (float) str_replace(',', '.', str_replace('.', '', $value));
        }

        $row->{$field} = $value ?: null;
        $row->save();
    }

    public function confirmDelete(int $id): void
    {
        $this->editingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Gate::authorize('project_expenses.delete');
        if ($this->editingId) {
            ProjectExpense::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('project_expenses.delete');
        ProjectExpense::whereIn('id', $this->selected)->delete();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        $this->deselectAll();
    }

    public function exportToExcel()
    {
        Gate::authorize('project_expenses.export');
        $rows = $this->buildQuery()->get();
        $filename = 'project-expenses-' . date('Y-m-d-His') . '-' . uniqid() . '.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new ProjectExpenseExport($rows), 'exports/' . $filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function openImportModal(): void
    {
        Gate::authorize('project_expenses.create');
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
        $service = new ProjectExpenseImportService();
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
            } elseif (str_contains($lower, 'date') || str_contains($lower, 'fecha')) {
                $map['expense_date'] = $idx;
            } elseif (str_contains($lower, 'category') || str_contains($lower, 'categoría') || str_contains($lower, 'categoria')) {
                $map['category'] = $idx;
            } elseif (str_contains($lower, 'description') || str_contains($lower, 'descripción') || str_contains($lower, 'descripcion') || str_contains($lower, 'desc')) {
                $map['description'] = $idx;
            } elseif (str_contains($lower, 'amount') || str_contains($lower, 'importe') || str_contains($lower, 'monto') || str_contains($lower, 'cantidad')) {
                $map['amount'] = $idx;
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importRows(): void
    {
        Gate::authorize('project_expenses.create');
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new ProjectExpenseImportService();
        $result = $service->importMappedData($path, $this->importColumnMap);

        $this->showImportModal = false;
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;

        if ($result['imported'] > 0) {
            $this->dispatch('notify', type: 'success', message: $result['imported'] . ' ' . __('app.rows_imported'));
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
        $this->formExpenseDate = '';
        $this->formCategory = '';
        $this->formDescription = '';
        $this->formAmount = '0';
        $this->resetValidation();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterPeriodId = '';
        $this->filterClientId = '';
        $this->filterProjectId = '';
        $this->filterCategory = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->buildQuery();
        $rows = $query->paginate($this->perPage);
        $allRows = $query->get();

        $totals = [
            'amount' => $allRows->sum('amount'),
        ];

        $categories = ProjectExpense::query()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return view('livewire.project-expenses.project-expense-page', [
            'rows' => $rows,
            'totals' => $totals,
            'categories' => $categories,
            'periods' => MonthlyPeriod::orderByDesc('year')->orderByDesc('month')->get(),
            'clients' => Client::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'projectMonths' => ProjectMonth::with(['monthlyPeriod', 'client', 'project'])->orderByDesc('id')->get(),
        ])->layout('layouts.app');
    }

    protected function buildQuery()
    {
        $query = ProjectExpense::query()
            ->with(['projectMonth.monthlyPeriod', 'projectMonth.client', 'projectMonth.project']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('category', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
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

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
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
