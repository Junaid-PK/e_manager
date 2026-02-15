<?php

namespace App\Livewire\CompaniesClients;

use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class ClientTable extends Component
{
    use WithPagination, WithSorting, WithFiltering, WithBulkActions;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;

    public string $formName = '';
    public string $formTaxId = '';
    public string $formContactPerson = '';
    public string $formEmail = '';
    public string $formPhone = '';
    public string $formAddress = '';
    public string $formNotes = '';

    protected function rules(): array
    {
        return [
            'formName' => 'required|string|max:255',
            'formTaxId' => 'nullable|string|max:50',
            'formContactPerson' => 'nullable|string|max:255',
            'formEmail' => 'nullable|email|max:255',
            'formPhone' => 'nullable|string|max:50',
            'formAddress' => 'nullable|string|max:1000',
            'formNotes' => 'nullable|string|max:2000',
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
        $client = Client::findOrFail($id);
        $this->editingId = $id;
        $this->formName = $client->name;
        $this->formTaxId = $client->tax_id ?? '';
        $this->formContactPerson = $client->contact_person ?? '';
        $this->formEmail = $client->email ?? '';
        $this->formPhone = $client->phone ?? '';
        $this->formAddress = $client->address ?? '';
        $this->formNotes = $client->notes ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->formName,
            'tax_id' => $this->formTaxId ?: null,
            'contact_person' => $this->formContactPerson ?: null,
            'email' => $this->formEmail ?: null,
            'phone' => $this->formPhone ?: null,
            'address' => $this->formAddress ?: null,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->editingId) {
            Client::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            Client::create($data);
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
            Client::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Client::whereIn('id', $this->selected)->delete();
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    protected function getPageItemIds(): array
    {
        return $this->getClients()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->buildQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function buildQuery()
    {
        $query = Client::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('tax_id', 'like', "%{$this->search}%")
                  ->orWhere('contact_person', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return $this->applySorting($query);
    }

    protected function getClients()
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    private function resetForm(): void
    {
        $this->formName = '';
        $this->formTaxId = '';
        $this->formContactPerson = '';
        $this->formEmail = '';
        $this->formPhone = '';
        $this->formAddress = '';
        $this->formNotes = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.companies-clients.client-table', [
            'clients' => $this->getClients(),
        ]);
    }
}
