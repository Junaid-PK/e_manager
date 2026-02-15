<?php

namespace App\Livewire\Movements;

use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\BankAccount;
use App\Models\BankMovement;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MovementPage extends Component
{
    use WithPagination, WithSorting, WithFiltering, WithBulkActions;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public bool $showCategoryModal = false;
    public string $bulkCategory = '';

    #[Url(as: 'bank_account_id')]
    public string $filterBankAccountId = '';
    public string $filterType = '';
    public string $filterDirection = 'all';
    public string $filterCategory = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public string $formBankAccountId = '';
    public string $formDate = '';
    public string $formValueDate = '';
    public string $formType = 'transfer';
    public string $formConcept = '';
    public string $formBeneficiary = '';
    public string $formReference = '';
    public string $formDeposit = '';
    public string $formWithdrawal = '';
    public string $formCategory = '';
    public string $formNotes = '';

    protected function rules(): array
    {
        return [
            'formBankAccountId' => 'required|exists:bank_accounts,id',
            'formDate' => 'required|date',
            'formValueDate' => 'nullable|date',
            'formType' => 'required|in:transfer,commission,card_payment,direct_debit,other',
            'formConcept' => 'required|string|max:500',
            'formBeneficiary' => 'nullable|string|max:255',
            'formReference' => 'nullable|string|max:255',
            'formDeposit' => 'nullable|numeric|min:0',
            'formWithdrawal' => 'nullable|numeric|min:0',
            'formCategory' => 'nullable|string|max:100',
            'formNotes' => 'nullable|string|max:2000',
        ];
    }

    public function updatedFilterBankAccountId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDirection(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function setDirection(string $direction): void
    {
        $this->filterDirection = $direction;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterBankAccountId = '';
        $this->filterType = '';
        $this->filterDirection = 'all';
        $this->filterCategory = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        if ($this->filterBankAccountId) {
            $this->formBankAccountId = $this->filterBankAccountId;
        }
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $movement = BankMovement::findOrFail($id);
        $this->editingId = $id;
        $this->formBankAccountId = (string) $movement->bank_account_id;
        $this->formDate = $movement->date->format('Y-m-d');
        $this->formValueDate = $movement->value_date ? $movement->value_date->format('Y-m-d') : '';
        $this->formType = $movement->type;
        $this->formConcept = $movement->concept;
        $this->formBeneficiary = $movement->beneficiary ?? '';
        $this->formReference = $movement->reference ?? '';
        $this->formDeposit = $movement->deposit ? (string) $movement->deposit : '';
        $this->formWithdrawal = $movement->withdrawal ? (string) $movement->withdrawal : '';
        $this->formCategory = $movement->category ?? '';
        $this->formNotes = $movement->notes ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'bank_account_id' => $this->formBankAccountId,
            'date' => $this->formDate,
            'value_date' => $this->formValueDate ?: null,
            'type' => $this->formType,
            'concept' => $this->formConcept,
            'beneficiary' => $this->formBeneficiary ?: null,
            'reference' => $this->formReference ?: null,
            'deposit' => $this->formDeposit !== '' ? $this->formDeposit : null,
            'withdrawal' => $this->formWithdrawal !== '' ? $this->formWithdrawal : null,
            'category' => $this->formCategory ?: null,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->editingId) {
            $movement = BankMovement::findOrFail($this->editingId);
            $movement->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            $data['balance'] = 0;
            $data['import_source'] = 'manual';
            BankMovement::create($data);
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
            BankMovement::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        BankMovement::whereIn('id', $this->selected)->delete();
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function openCategoryModal(): void
    {
        $this->bulkCategory = '';
        $this->showCategoryModal = true;
    }

    public function applyCategoryToSelected(): void
    {
        BankMovement::whereIn('id', $this->selected)->update(['category' => $this->bulkCategory ?: null]);
        $this->showCategoryModal = false;
        $this->bulkCategory = '';
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    protected function getPageItemIds(): array
    {
        return $this->getMovements()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->buildQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function buildQuery()
    {
        $query = BankMovement::with('bankAccount');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('concept', 'like', "%{$this->search}%")
                  ->orWhere('beneficiary', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterBankAccountId) {
            $query->where('bank_account_id', $this->filterBankAccountId);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterDirection === 'deposit') {
            $query->whereNotNull('deposit')->where('deposit', '>', 0);
        } elseif ($this->filterDirection === 'withdrawal') {
            $query->whereNotNull('withdrawal')->where('withdrawal', '>', 0);
        }

        if ($this->filterCategory) {
            $query->where('category', 'like', "%{$this->filterCategory}%");
        }

        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
        }

        return $this->applySorting($query);
    }

    protected function getMovements()
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    private function resetForm(): void
    {
        $this->formBankAccountId = '';
        $this->formDate = '';
        $this->formValueDate = '';
        $this->formType = 'transfer';
        $this->formConcept = '';
        $this->formBeneficiary = '';
        $this->formReference = '';
        $this->formDeposit = '';
        $this->formWithdrawal = '';
        $this->formCategory = '';
        $this->formNotes = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.movements.movement-page', [
            'movements' => $this->getMovements(),
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(),
        ])->layout('layouts.app');
    }
}
