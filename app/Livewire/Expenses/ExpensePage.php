<?php

namespace App\Livewire\Expenses;

use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Company;
use App\Models\Expense;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ExpensePage extends Component
{
    use WithPagination, WithSorting, WithFiltering, WithBulkActions, WithFileUploads;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showCategoryModal = false;
    public bool $showReceiptPreview = false;
    public ?int $editingId = null;
    public string $previewReceiptUrl = '';
    public string $bulkCategory = '';

    public string $filterCompanyId = '';
    public string $filterCategory = '';
    public string $filterPaymentMethod = '';
    public string $filterRecurring = '';
    public string $filterVendor = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public string $formCompanyId = '';
    public string $formCategory = '';
    public string $formDescription = '';
    public string $formAmount = '0';
    public string $formDate = '';
    public string $formVendor = '';
    public string $formPaymentMethod = 'cash';
    public $formReceipt;
    public bool $formRecurring = false;
    public string $formRecurringFrequency = 'monthly';
    public string $formNotes = '';
    public string $existingReceiptPath = '';

    protected function rules(): array
    {
        return [
            'formCompanyId' => 'nullable|exists:companies,id',
            'formCategory' => 'required|string|max:255',
            'formDescription' => 'required|string|max:500',
            'formAmount' => 'required|numeric|min:0',
            'formDate' => 'required|date',
            'formVendor' => 'nullable|string|max:255',
            'formPaymentMethod' => 'required|in:cash,bank_transfer,card,check,other',
            'formReceipt' => 'nullable|file|max:10240',
            'formRecurring' => 'boolean',
            'formRecurringFrequency' => 'nullable|in:monthly,quarterly,yearly',
            'formNotes' => 'nullable|string|max:5000',
        ];
    }

    public function updatedFilterCompanyId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRecurring(): void
    {
        $this->resetPage();
    }

    public function updatedFilterVendor(): void
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

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $this->editingId = $id;
        $this->formCompanyId = (string) ($expense->company_id ?? '');
        $this->formCategory = $expense->category;
        $this->formDescription = $expense->description;
        $this->formAmount = (string) $expense->amount;
        $this->formDate = $expense->date?->format('Y-m-d') ?? '';
        $this->formVendor = $expense->vendor ?? '';
        $this->formPaymentMethod = $expense->payment_method;
        $this->formRecurring = (bool) $expense->recurring;
        $this->formRecurringFrequency = $expense->recurring_frequency ?? 'monthly';
        $this->formNotes = $expense->notes ?? '';
        $this->existingReceiptPath = $expense->receipt_path ?? '';
        $this->formReceipt = null;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'company_id' => $this->formCompanyId ?: null,
            'category' => $this->formCategory,
            'description' => $this->formDescription,
            'amount' => (float) $this->formAmount,
            'date' => $this->formDate,
            'vendor' => $this->formVendor ?: null,
            'payment_method' => $this->formPaymentMethod,
            'recurring' => $this->formRecurring,
            'recurring_frequency' => $this->formRecurring ? $this->formRecurringFrequency : null,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->formReceipt) {
            $data['receipt_path'] = $this->formReceipt->store('receipts', 'public');
        }

        if ($this->editingId) {
            Expense::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            Expense::create($data);
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
            Expense::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Expense::whereIn('id', $this->selected)->delete();
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function bulkCategoryUpdate(): void
    {
        if ($this->bulkCategory && count($this->selected) > 0) {
            Expense::whereIn('id', $this->selected)->update(['category' => $this->bulkCategory]);
            $this->showCategoryModal = false;
            $this->bulkCategory = '';
            $this->deselectAll();
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        }
    }

    public function openReceiptPreview(int $id): void
    {
        $expense = Expense::findOrFail($id);
        if ($expense->receipt_path) {
            $this->previewReceiptUrl = Storage::disk('public')->url($expense->receipt_path);
            $this->showReceiptPreview = true;
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterCompanyId = '';
        $this->filterCategory = '';
        $this->filterPaymentMethod = '';
        $this->filterRecurring = '';
        $this->filterVendor = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    protected function buildQuery()
    {
        $query = Expense::query()->with(['company']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($this->filterCompanyId) {
            $query->where('company_id', $this->filterCompanyId);
        }

        if ($this->filterCategory) {
            $query->where('category', 'like', "%{$this->filterCategory}%");
        }

        if ($this->filterPaymentMethod) {
            $query->where('payment_method', $this->filterPaymentMethod);
        }

        if ($this->filterRecurring !== '') {
            if ($this->filterRecurring === '1') {
                $query->where('recurring', true);
            } elseif ($this->filterRecurring === '0') {
                $query->where('recurring', false);
            }
        }

        if ($this->filterVendor) {
            $query->where('vendor', 'like', "%{$this->filterVendor}%");
        }

        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
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

    protected function getCategorySummary(): array
    {
        $query = Expense::query();

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($this->filterCompanyId) {
            $query->where('company_id', $this->filterCompanyId);
        }

        if ($this->filterCategory) {
            $query->where('category', 'like', "%{$this->filterCategory}%");
        }

        if ($this->filterPaymentMethod) {
            $query->where('payment_method', $this->filterPaymentMethod);
        }

        if ($this->filterRecurring !== '') {
            if ($this->filterRecurring === '1') {
                $query->where('recurring', true);
            } elseif ($this->filterRecurring === '0') {
                $query->where('recurring', false);
            }
        }

        if ($this->filterVendor) {
            $query->where('vendor', 'like', "%{$this->filterVendor}%");
        }

        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
        }

        return $query->selectRaw('category, SUM(amount) as total_amount')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->pluck('total_amount', 'category')
            ->toArray();
    }

    private function resetForm(): void
    {
        $this->formCompanyId = '';
        $this->formCategory = '';
        $this->formDescription = '';
        $this->formAmount = '0';
        $this->formDate = '';
        $this->formVendor = '';
        $this->formPaymentMethod = 'cash';
        $this->formReceipt = null;
        $this->formRecurring = false;
        $this->formRecurringFrequency = 'monthly';
        $this->formNotes = '';
        $this->existingReceiptPath = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.expenses.expense-page', [
            'expenses' => $this->buildQuery()->paginate($this->perPage),
            'companies' => Company::orderBy('name')->get(),
            'categorySummary' => $this->getCategorySummary(),
        ])->layout('layouts.app');
    }
}
