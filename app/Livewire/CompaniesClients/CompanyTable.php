<?php

namespace App\Livewire\CompaniesClients;

use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Company;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyTable extends Component
{
    use WithPagination, WithSorting, WithFiltering, WithBulkActions;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;

    public string $formName = '';
    public string $formTaxId = '';
    public string $formEmail = '';
    public string $formPhone = '';
    public string $formAddress = '';

    protected function rules(): array
    {
        return [
            'formName' => 'required|string|max:255',
            'formTaxId' => 'nullable|string|max:50',
            'formEmail' => 'nullable|email|max:255',
            'formPhone' => 'nullable|string|max:50',
            'formAddress' => 'nullable|string|max:1000',
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
        $company = Company::findOrFail($id);
        $this->editingId = $id;
        $this->formName = $company->name;
        $this->formTaxId = $company->tax_id ?? '';
        $this->formEmail = $company->email ?? '';
        $this->formPhone = $company->phone ?? '';
        $this->formAddress = $company->address ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->formName,
            'tax_id' => $this->formTaxId ?: null,
            'email' => $this->formEmail ?: null,
            'phone' => $this->formPhone ?: null,
            'address' => $this->formAddress ?: null,
        ];

        if ($this->editingId) {
            Company::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            Company::create($data);
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
        if ($this->editingId) {
            Company::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Company::whereIn('id', $this->selected)->delete();
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    protected function getPageItemIds(): array
    {
        return $this->getCompanies()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->buildQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function buildQuery()
    {
        $query = Company::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('tax_id', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return $this->applySorting($query);
    }

    protected function getCompanies()
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    private function resetForm(): void
    {
        $this->formName = '';
        $this->formTaxId = '';
        $this->formEmail = '';
        $this->formPhone = '';
        $this->formAddress = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.companies-clients.company-table', [
            'companies' => $this->getCompanies(),
        ]);
    }
}
