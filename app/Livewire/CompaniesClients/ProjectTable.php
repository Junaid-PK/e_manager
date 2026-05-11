<?php

namespace App\Livewire\CompaniesClients;

use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectTable extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public string $formCompanyId = '';

    public string $formName = '';

    public string $formCode = '';

    public string $formDescription = '';

    public string $formLocation = '';

    public string $formStatus = 'active';

    protected function rules(): array
    {
        return [
            'formCompanyId' => 'required|exists:companies,id',
            'formName' => 'required|string|max:255',
            'formCode' => 'nullable|string|max:100',
            'formDescription' => 'nullable|string|max:1000',
            'formLocation' => 'nullable|string|max:500',
            'formStatus' => 'required|in:active,inactive',
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
        $project = Project::findOrFail($id);
        $this->editingId = $id;
        $this->formCompanyId = (string) $project->company_id;
        $this->formName = $project->name;
        $this->formCode = $project->code ?? '';
        $this->formDescription = $project->description ?? '';
        $this->formLocation = $project->location ?? '';
        $this->formStatus = $project->status;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('companies_clients.edit');
        } else {
            Gate::authorize('companies_clients.create');
        }

        $this->validate();

        $data = [
            'company_id' => $this->formCompanyId,
            'name' => $this->formName,
            'code' => $this->formCode ?: null,
            'description' => $this->formDescription ?: null,
            'location' => $this->formLocation ?: null,
            'status' => $this->formStatus,
        ];

        if ($this->editingId) {
            Project::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            Project::create($data);
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
        Gate::authorize('companies_clients.delete');
        if ($this->editingId) {
            Project::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('companies_clients.delete');
        Project::whereIn('id', $this->selected)->delete();
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    protected function getPageItemIds(): array
    {
        return $this->getProjects()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->buildQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function buildQuery()
    {
        $query = Project::query()->with('company:id,name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('location', 'like', "%{$this->search}%")
                    ->orWhereHas('company', function ($cq) {
                        $cq->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        return $this->applySorting($query);
    }

    protected function getProjects()
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    private function resetForm(): void
    {
        $this->formCompanyId = '';
        $this->formName = '';
        $this->formCode = '';
        $this->formDescription = '';
        $this->formLocation = '';
        $this->formStatus = 'active';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.companies-clients.project-table', [
            'projects' => $this->getProjects(),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
