<?php

namespace App\Livewire\Expenses;

use App\Models\ExpenseCif;
use App\Models\ExpenseProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ExpenseListadoConfigPage extends Component
{
    public string $activeTab = 'providers';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public string $deleteTarget = '';

    public string $providerName = '';

    public int $providerSortOrder = 0;

    public string $cifCode = '';

    public int $cifSortOrder = 0;

    public string $searchProviders = '';

    public string $searchCifs = '';

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function createProvider(): void
    {
        Gate::authorize('expenses.edit');
        $this->resetProviderForm();
        $this->editingId = null;
        $this->providerSortOrder = (int) ExpenseProvider::max('sort_order') + 1;
        $this->showModal = true;
    }

    public function editProvider(int $id): void
    {
        Gate::authorize('expenses.edit');
        $p = ExpenseProvider::findOrFail($id);
        $this->editingId = $id;
        $this->providerName = $p->name;
        $this->providerSortOrder = $p->sort_order;
        $this->showModal = true;
    }

    public function saveProvider(): void
    {
        Gate::authorize('expenses.edit');
        $this->validate([
            'providerName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_providers', 'name')->ignore($this->editingId),
            ],
            'providerSortOrder' => 'integer|min:0',
        ]);

        $data = [
            'name' => trim($this->providerName),
            'sort_order' => $this->providerSortOrder,
        ];

        if ($this->editingId) {
            ExpenseProvider::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            ExpenseProvider::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showModal = false;
        $this->resetProviderForm();
    }

    public function createCif(): void
    {
        Gate::authorize('expenses.edit');
        $this->resetCifForm();
        $this->editingId = null;
        $this->cifSortOrder = (int) ExpenseCif::max('sort_order') + 1;
        $this->showModal = true;
    }

    public function editCif(int $id): void
    {
        Gate::authorize('expenses.edit');
        $c = ExpenseCif::findOrFail($id);
        $this->editingId = $id;
        $this->cifCode = $c->code;
        $this->cifSortOrder = $c->sort_order;
        $this->showModal = true;
    }

    public function saveCif(): void
    {
        Gate::authorize('expenses.edit');
        $normalized = mb_strtoupper(trim($this->cifCode));
        $this->cifCode = $normalized;
        $this->validate([
            'cifCode' => [
                'required',
                'string',
                'max:32',
                Rule::unique('expense_cifs', 'code')->ignore($this->editingId),
            ],
            'cifSortOrder' => 'integer|min:0',
        ]);

        $data = [
            'code' => $normalized,
            'sort_order' => $this->cifSortOrder,
        ];

        if ($this->editingId) {
            ExpenseCif::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            ExpenseCif::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showModal = false;
        $this->resetCifForm();
    }

    public function confirmDelete(string $kind, int $id): void
    {
        Gate::authorize('expenses.edit');
        $this->deleteTarget = $kind.':'.$id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Gate::authorize('expenses.edit');
        if ($this->deleteTarget === '') {
            return;
        }
        [$kind, $idStr] = explode(':', $this->deleteTarget, 2);
        $id = (int) $idStr;
        if ($kind === 'provider') {
            ExpenseProvider::findOrFail($id)->delete();
        } elseif ($kind === 'cif') {
            ExpenseCif::findOrFail($id)->delete();
        }
        $this->showDeleteModal = false;
        $this->deleteTarget = '';
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    private function resetProviderForm(): void
    {
        $this->providerName = '';
        $this->providerSortOrder = 0;
        $this->editingId = null;
        $this->resetValidation();
    }

    private function resetCifForm(): void
    {
        $this->cifCode = '';
        $this->cifSortOrder = 0;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        $providersQuery = ExpenseProvider::query()->orderBy('sort_order')->orderBy('name');
        if ($this->searchProviders !== '') {
            $s = '%'.$this->searchProviders.'%';
            $providersQuery->where('name', 'like', $s);
        }
        $providers = $providersQuery->get();

        $cifsQuery = ExpenseCif::query()->orderBy('sort_order')->orderBy('code');
        if ($this->searchCifs !== '') {
            $s = '%'.$this->searchCifs.'%';
            $cifsQuery->where('code', 'like', $s);
        }
        $cifs = $cifsQuery->get();

        return view('livewire.expenses.expense-listado-config-page', [
            'providers' => $providers,
            'cifs' => $cifs,
        ])->layout('layouts.app');
    }
}
