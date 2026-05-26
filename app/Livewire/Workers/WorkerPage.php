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
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class WorkerPage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting, WithFileUploads;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showImportModal = false;
    public ?int $editingId = null;

    public string $formFullName = '';
    public string $formNie = '';
    public string $formBankAccount = '';

    public $importFile = null;
    public array $importPreview = [];
    public array $importColumnMap = [];
    public int $importStep = 1;

    protected function rules(): array
    {
        return [
            'formFullName' => 'required|string|max:255',
            'formNie' => 'nullable|string|max:50',
            'formBankAccount' => 'nullable|string|max:255',
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $worker = Worker::findOrFail($id);
        $this->editingId = $id;
        $this->formFullName = $worker->full_name;
        $this->formNie = $worker->nie ?? '';
        $this->formBankAccount = $worker->bank_account ?? '';
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

        $data = [
            'full_name' => $this->formFullName,
            'nie' => $this->formNie ?: null,
            'bank_account' => $this->formBankAccount ?: null,
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
        $this->showImportModal = true;
    }

    public function updatedImportFile(): void
    {
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new WorkerImportService();
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
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importWorkers(): void
    {
        Gate::authorize('workers.create');
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new WorkerImportService();
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
        $this->formFullName = '';
        $this->formNie = '';
        $this->formBankAccount = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.workers.worker-page', [
            'workers' => $this->buildQuery()->paginate($this->perPage),
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
