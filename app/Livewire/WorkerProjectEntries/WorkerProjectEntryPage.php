<?php

namespace App\Livewire\WorkerProjectEntries;

use App\Exports\WorkerProjectEntryExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\MonthlyPeriod;
use App\Models\ProjectMonth;
use App\Models\Worker;
use App\Models\WorkerProjectEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class WorkerProjectEntryPage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showBulkCreateModal = false;
    public ?int $editingId = null;

    public string $filterPeriodId = '';
    public string $filterProjectMonthId = '';
    public string $filterWorkerId = '';

    public string $formProjectMonthId = '';
    public string $formWorkerId = '';
    public string $formSpecialNote = '';
    public string $formSocialSecurity = '0';
    public string $formHours = '0';
    public string $formDays = '0';
    public string $formRate = '0';
    public string $formPaidAmount = '0';

    public array $bulkRows = [];

    protected function rules(): array
    {
        return [
            'formProjectMonthId' => 'required|exists:project_months,id',
            'formWorkerId' => 'required|exists:workers,id',
            'formSpecialNote' => 'nullable|string|max:500',
            'formSocialSecurity' => 'nullable|numeric|min:0',
            'formHours' => 'nullable|numeric|min:0',
            'formDays' => 'nullable|numeric|min:0',
            'formRate' => 'nullable|numeric|min:0',
            'formPaidAmount' => 'nullable|numeric|min:0',
        ];
    }

    public function updatedFilterPeriodId(): void
    {
        $this->filterProjectMonthId = '';
        $this->resetPage();
    }

    public function updatedFilterProjectMonthId(): void
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
        $row = WorkerProjectEntry::findOrFail($id);
        $this->editingId = $id;
        $this->formProjectMonthId = (string) $row->project_month_id;
        $this->formWorkerId = (string) $row->worker_id;
        $this->formSpecialNote = $row->special_note ?? '';
        $this->formSocialSecurity = (string) $row->social_security;
        $this->formHours = (string) $row->hours;
        $this->formDays = (string) $row->days;
        $this->formRate = (string) $row->rate;
        $this->formPaidAmount = (string) $row->paid_amount;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('worker_project_entries.edit');
        } else {
            Gate::authorize('worker_project_entries.create');
        }

        $this->validate();

        $data = [
            'project_month_id' => (int) $this->formProjectMonthId,
            'worker_id' => (int) $this->formWorkerId,
            'special_note' => $this->formSpecialNote ?: null,
            'social_security' => (float) ($this->formSocialSecurity ?: 0),
            'hours' => (float) ($this->formHours ?: 0),
            'days' => (float) ($this->formDays ?: 0),
            'rate' => (float) ($this->formRate ?: 0),
            'paid_amount' => (float) ($this->formPaidAmount ?: 0),
        ];

        if ($this->editingId) {
            WorkerProjectEntry::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            WorkerProjectEntry::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function quickUpdateField(int $id, string $field, string $value): void
    {
        Gate::authorize('worker_project_entries.edit');

        $row = WorkerProjectEntry::findOrFail($id);
        $numericValue = (float) str_replace(',', '.', str_replace('.', '', $value));

        $allowedFields = [
            'social_security',
            'hours',
            'days',
            'rate',
            'paid_amount',
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
        Gate::authorize('worker_project_entries.delete');
        if ($this->editingId) {
            WorkerProjectEntry::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('worker_project_entries.delete');
        WorkerProjectEntry::whereIn('id', $this->selected)->delete();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        $this->deselectAll();
    }

    public function exportToExcel()
    {
        Gate::authorize('worker_project_entries.export');
        $rows = $this->buildQuery()->get();
        $filename = 'worker-project-entries-' . date('Y-m-d-His') . '-' . uniqid() . '.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new WorkerProjectEntryExport($rows), 'exports/' . $filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function openBulkCreate(): void
    {
        Gate::authorize('worker_project_entries.create');
        $this->bulkRows = [
            ['worker_id' => '', 'special_note' => '', 'social_security' => '0', 'hours' => '0', 'days' => '0', 'rate' => '0', 'paid_amount' => '0'],
            ['worker_id' => '', 'special_note' => '', 'social_security' => '0', 'hours' => '0', 'days' => '0', 'rate' => '0', 'paid_amount' => '0'],
            ['worker_id' => '', 'special_note' => '', 'social_security' => '0', 'hours' => '0', 'days' => '0', 'rate' => '0', 'paid_amount' => '0'],
            ['worker_id' => '', 'special_note' => '', 'social_security' => '0', 'hours' => '0', 'days' => '0', 'rate' => '0', 'paid_amount' => '0'],
            ['worker_id' => '', 'special_note' => '', 'social_security' => '0', 'hours' => '0', 'days' => '0', 'rate' => '0', 'paid_amount' => '0'],
        ];
        $this->formProjectMonthId = $this->filterProjectMonthId ?: '';
        $this->showBulkCreateModal = true;
    }

    public function addBulkRow(): void
    {
        $this->bulkRows[] = ['worker_id' => '', 'special_note' => '', 'social_security' => '0', 'hours' => '0', 'days' => '0', 'rate' => '0', 'paid_amount' => '0'];
    }

    public function removeBulkRow(int $index): void
    {
        unset($this->bulkRows[$index]);
        $this->bulkRows = array_values($this->bulkRows);
    }

    public function saveBulk(): void
    {
        Gate::authorize('worker_project_entries.create');

        if (! $this->formProjectMonthId) {
            $this->dispatch('notify', type: 'error', message: __('app.select_project_month'));
            return;
        }

        $created = 0;
        foreach ($this->bulkRows as $row) {
            if (empty($row['worker_id'])) {
                continue;
            }

            WorkerProjectEntry::create([
                'project_month_id' => (int) $this->formProjectMonthId,
                'worker_id' => (int) $row['worker_id'],
                'special_note' => $row['special_note'] ?: null,
                'social_security' => (float) ($row['social_security'] ?: 0),
                'hours' => (float) ($row['hours'] ?: 0),
                'days' => (float) ($row['days'] ?: 0),
                'rate' => (float) ($row['rate'] ?: 0),
                'paid_amount' => (float) ($row['paid_amount'] ?: 0),
            ]);
            $created++;
        }

        $this->showBulkCreateModal = false;
        $this->bulkRows = [];

        if ($created > 0) {
            $this->dispatch('notify', type: 'success', message: $created . ' ' . __('app.rows_created'));
        }
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->formProjectMonthId = $this->filterProjectMonthId ?: '';
        $this->formWorkerId = '';
        $this->formSpecialNote = '';
        $this->formSocialSecurity = '0';
        $this->formHours = '0';
        $this->formDays = '0';
        $this->formRate = '0';
        $this->formPaidAmount = '0';
        $this->resetValidation();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterPeriodId = '';
        $this->filterProjectMonthId = '';
        $this->filterWorkerId = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->buildQuery();
        $rows = $query->paginate($this->perPage);
        $allRows = $query->get();

        $totals = [
            'social_security' => $allRows->sum('social_security'),
            'hours' => $allRows->sum('hours'),
            'days' => $allRows->sum('days'),
            'total_amount' => $allRows->sum('total_amount'),
            'paid_amount' => $allRows->sum('paid_amount'),
        ];

        return view('livewire.worker-project-entries.worker-project-entry-page', [
            'rows' => $rows,
            'totals' => $totals,
            'periods' => MonthlyPeriod::orderByDesc('year')->orderByDesc('month')->get(),
            'workers' => Worker::orderBy('full_name')->get(),
            'projectMonths' => $this->getFilteredProjectMonths(),
        ])->layout('layouts.app');
    }

    protected function getFilteredProjectMonths()
    {
        $query = ProjectMonth::with(['monthlyPeriod', 'client', 'project'])->orderByDesc('id');

        if ($this->filterPeriodId) {
            $query->where('monthly_period_id', (int) $this->filterPeriodId);
        }

        return $query->get();
    }

    protected function buildQuery()
    {
        $query = WorkerProjectEntry::query()
            ->with(['projectMonth.monthlyPeriod', 'projectMonth.client', 'projectMonth.project', 'worker']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('worker', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('projectMonth', fn ($sq) => $sq->whereHas('client', fn ($sq2) => $sq2->where('name', 'like', "%{$search}%")))
                    ->orWhereHas('projectMonth', fn ($sq) => $sq->whereHas('project', fn ($sq2) => $sq2->where('name', 'like', "%{$search}%")));
            });
        }

        if ($this->filterProjectMonthId) {
            $query->where('project_month_id', (int) $this->filterProjectMonthId);
        }

        if ($this->filterWorkerId) {
            $query->where('worker_id', (int) $this->filterWorkerId);
        }

        if ($this->filterPeriodId) {
            $query->whereHas('projectMonth', fn ($q) => $q->where('monthly_period_id', (int) $this->filterPeriodId));
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
