<?php

namespace App\Livewire\BankAccounts;

use App\Models\BankAccount;
use Livewire\Component;

class BankAccountPage extends Component
{
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public string $search = '';

    public string $formBankName = '';
    public string $formAccountNumber = '';
    public string $formHolderName = '';
    public string $formCurrency = 'EUR';
    public string $formInitialBalance = '0.00';

    protected function rules(): array
    {
        return [
            'formBankName' => 'required|string|max:255',
            'formAccountNumber' => 'required|string|max:100',
            'formHolderName' => 'required|string|max:255',
            'formCurrency' => 'required|string|max:10',
            'formInitialBalance' => 'required|numeric',
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
        $account = BankAccount::findOrFail($id);
        $this->editingId = $id;
        $this->formBankName = $account->bank_name;
        $this->formAccountNumber = $account->account_number;
        $this->formHolderName = $account->holder_name;
        $this->formCurrency = $account->currency;
        $this->formInitialBalance = $account->initial_balance;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'bank_name' => $this->formBankName,
            'account_number' => $this->formAccountNumber,
            'holder_name' => $this->formHolderName,
            'currency' => $this->formCurrency,
            'initial_balance' => $this->formInitialBalance,
        ];

        if ($this->editingId) {
            BankAccount::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            $data['current_balance'] = $this->formInitialBalance;
            BankAccount::create($data);
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
            BankAccount::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    protected function getAccounts()
    {
        $query = BankAccount::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('bank_name', 'like', "%{$this->search}%")
                  ->orWhere('account_number', 'like', "%{$this->search}%")
                  ->orWhere('holder_name', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('bank_name')->get();
    }

    private function resetForm(): void
    {
        $this->formBankName = '';
        $this->formAccountNumber = '';
        $this->formHolderName = '';
        $this->formCurrency = 'EUR';
        $this->formInitialBalance = '0.00';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.bank-accounts.bank-account-page', [
            'accounts' => $this->getAccounts(),
        ])->layout('layouts.app');
    }
}
