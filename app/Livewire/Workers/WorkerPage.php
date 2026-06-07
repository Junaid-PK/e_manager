<?php

namespace App\Livewire\Workers;

use App\Exports\WorkerExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Worker;
use App\Services\WorkerImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class WorkerPage extends Component
{
    use WithBulkActions, WithFileUploads, WithFiltering, WithPagination, WithSorting;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showImportModal = false;

    public bool $isCreating = false;

    public ?int $editingId = null;

    public string $formFullName = '';

    public string $formNie = '';

    public string $formBankAccount = '';

    public string $formRate = '0';

    public $importFile = null;

    public array $importPreview = [];

    public array $importColumnMap = [];

    public int $importStep = 1;

    public ?array $lastImportResult = null;

    public string $statusFilter = '';

    protected function rules(): array
    {
        $rules = [
            'formFullName' => 'required|string|max:255',
            'formNie' => 'nullable|string|max:50',
            'formBankAccount' => 'nullable|string|max:255',
            'formRate' => 'nullable|numeric|min:0',
        ];

        return $rules;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->isCreating = true;
    }

    public function cancelCreate(): void
    {
        $this->isCreating = false;
        $this->resetForm();
    }

    public function saveInline(): void
    {
        Gate::authorize('workers.create');

        $this->validate();

        // Check for duplicate NIE or bank account
        $duplicateError = $this->checkDuplicate();
        if ($duplicateError) {
            $this->dispatch('notify', type: 'error', message: $duplicateError);

            return;
        }

        Worker::create([
            'full_name' => $this->formFullName,
            'nie' => $this->formNie ?: null,
            'bank_account' => $this->formBankAccount ?: null,
            'rate' => (float) ($this->formRate ?: 0),
        ]);

        $this->isCreating = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
    }

    public function edit(int $id): void
    {
        $worker = Worker::findOrFail($id);
        $this->editingId = $id;
        $this->formFullName = $worker->full_name;
        $this->formNie = $worker->nie ?? '';
        $this->formBankAccount = $worker->bank_account ?? '';
        $this->formRate = (string) $worker->rate;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('workers.edit');
        } else {
            Gate::authorize('workers.create');
        }

        $this->validate();

        // Check for duplicate NIE or bank account
        $duplicateError = $this->checkDuplicate();
        if ($duplicateError) {
            $this->dispatch('notify', type: 'error', message: $duplicateError);

            return;
        }

        $data = [
            'full_name' => $this->formFullName,
            'nie' => $this->formNie ?: null,
            'bank_account' => $this->formBankAccount ?: null,
            'rate' => (float) ($this->formRate ?: 0),
        ];

        if ($this->editingId) {
            Worker::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            Worker::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    private function checkDuplicate(): ?string
    {
        $nie = trim($this->formNie);
        $bankAccount = trim($this->formBankAccount);

        if (empty($nie) && empty($bankAccount)) {
            return null;
        }

        $query = Worker::query();

        if ($this->editingId) {
            $query->where('id', '!=', $this->editingId);
        }

        $query->where(function ($q) use ($nie, $bankAccount) {
            if (! empty($nie)) {
                $q->where('nie', $nie);
            }
            if (! empty($bankAccount)) {
                $q->orWhere('bank_account', $bankAccount);
            }
        });

        $existing = $query->first();

        if ($existing) {
            if (! empty($nie) && $existing->nie === $nie) {
                return __('app.worker_with_nie_exists', ['nie' => $nie, 'name' => $existing->full_name]);
            }
            if (! empty($bankAccount) && $existing->bank_account === $bankAccount) {
                return __('app.worker_with_bank_account_exists', ['bank_account' => $bankAccount, 'name' => $existing->full_name]);
            }
        }

        return null;
    }

    public function quickUpdateField(int $id, string $field, string $value): void
    {
        Gate::authorize('workers.edit');

        $worker = Worker::findOrFail($id);

        $allowedFields = ['full_name', 'nie', 'bank_account', 'rate'];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        if ($field === 'rate') {
            $worker->{$field} = (float) ($value ?: 0);
        } else {
            $worker->{$field} = $value ?: null;
        }
        $worker->save();
    }

    public function confirmDelete(int $id): void
    {
        $this->editingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Gate::authorize('workers.delete');
        if ($this->editingId) {
            Worker::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('workers.delete');
        Worker::whereIn('id', $this->selected)->delete();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        $this->deselectAll();
    }

    public function exportToExcel()
    {
        Gate::authorize('workers.export');
        $workers = $this->buildQuery()->get();
        $filename = 'workers-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new WorkerExport($workers), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function openImportModal(): void
    {
        Gate::authorize('workers.create');
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;
        $this->lastImportResult = null;
        $this->showImportModal = true;
    }

    public function updatedImportFile(): void
    {
        if (! $this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new WorkerImportService;
        $result = $service->parseFile($path);

        $this->importPreview = $result;

        $headers = $result['headers'] ?? [];
        $map = [];
        foreach ($headers as $idx => $header) {
            $lower = mb_strtolower(trim($header));
            if (str_contains($lower, 'name') || str_contains($lower, 'nombre') || str_contains($lower, 'full')) {
                $map['full_name'] = $idx;
            } elseif (str_contains($lower, 'nie') || str_contains($lower, 'dni') || str_contains($lower, 'nif')) {
                $map['nie'] = $idx;
            } elseif (str_contains($lower, 'bank') || str_contains($lower, 'cuenta') || str_contains($lower, 'iban')) {
                $map['bank_account'] = $idx;
            } elseif (str_contains($lower, 'rate') || str_contains($lower, 'precio') || str_contains($lower, 'tarifa') || str_contains($lower, 'price')) {
                $map['rate'] = $idx;
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importWorkers(): void
    {
        Gate::authorize('workers.create');
        if (! $this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $fileName = $this->importFile->getClientOriginalName();
        $service = new WorkerImportService;
        $result = $service->importMappedData($path, $this->importColumnMap, $fileName);

        $this->showImportModal = false;
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;
        $this->lastImportResult = $result;

        if ($result['imported'] > 0 || $result['skipped'] > 0) {
            $message = $result['imported'].' '.__('app.rows_imported');
            if ($result['new'] > 0) {
                $message .= ' ('.$result['new'].' '.__('app.new').')';
            }
            if ($result['skipped'] > 0) {
                $message .= ' - '.$result['skipped'].' '.__('app.duplicate_workers_skipped');
            }
            $this->dispatch('notify', type: 'success', message: $message);
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
        $this->formFullName = '';
        $this->formNie = '';
        $this->formBankAccount = '';
        $this->formRate = '0';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.workers.worker-page', [
            'workers' => $this->buildQuery()->paginate($this->perPage),
            'statusCounts' => $this->getStatusCounts(),
        ])->layout('layouts.app');
    }

    protected function buildQuery()
    {
        $query = Worker::query();

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nie', 'like', "%{$search}%")
                    ->orWhere('bank_account', 'like', "%{$search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('import_status', $this->statusFilter);
        }

        return $this->applySorting($query);
    }

    protected function getStatusCounts(): array
    {
        return [
            'new' => Worker::newlyImported()->count(),
            'active' => Worker::active()->count(),
            'removed' => Worker::removed()->count(),
            'total' => Worker::count(),
        ];
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
