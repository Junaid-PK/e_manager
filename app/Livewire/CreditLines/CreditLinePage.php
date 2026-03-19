<?php

namespace App\Livewire\CreditLines;

use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\CreditLine;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class CreditLinePage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public string $filterEntityType = '';

    public string $filterStatus = '';

    public string $filterYear = '';

    public string $formEntityName = '';

    public string $formEntityType = 'bank';

    public string $formYear = '';

    public string $formTotalAmount = '0';

    public string $formAmountPaid = '0';

    public string $formInterestRate = '0';

    public string $formStartDate = '';

    public string $formDueDate = '';

    public string $formStatus = 'active';

    public string $formNotes = '';

    protected function rules(): array
    {
        return [
            'formEntityName' => 'required|string|max:255',
            'formEntityType' => 'required|in:bank,company',
            'formYear' => 'nullable|integer|min:2000|max:2099',
            'formTotalAmount' => 'required|numeric|min:0',
            'formAmountPaid' => 'nullable|numeric|min:0',
            'formInterestRate' => 'nullable|numeric|min:0|max:100',
            'formStartDate' => 'nullable|date',
            'formDueDate' => 'nullable|date',
            'formStatus' => 'required|in:active,paid_off,defaulted',
            'formNotes' => 'nullable|string|max:5000',
        ];
    }

    public function updatedFilterEntityType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterYear(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterEntityType = '';
        $this->filterStatus = '';
        $this->filterYear = '';
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
        $cl = CreditLine::findOrFail($id);
        $this->editingId = $id;
        $this->formEntityName = $cl->entity_name;
        $this->formEntityType = $cl->entity_type;
        $this->formYear = (string) ($cl->year ?? '');
        $this->formTotalAmount = (string) $cl->total_amount;
        $this->formAmountPaid = (string) $cl->amount_paid;
        $this->formInterestRate = (string) $cl->interest_rate;
        $this->formStartDate = $cl->start_date?->format('Y-m-d') ?? '';
        $this->formDueDate = $cl->due_date?->format('Y-m-d') ?? '';
        $this->formStatus = $cl->status;
        $this->formNotes = $cl->notes ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('credit_lines.edit');
        } else {
            Gate::authorize('credit_lines.create');
        }

        $this->validate();

        $totalAmount = (float) $this->formTotalAmount;
        $amountPaid = (float) ($this->formAmountPaid ?: 0);
        $amountRemaining = max(0, round($totalAmount - $amountPaid, 2));

        $data = [
            'entity_name' => $this->formEntityName,
            'entity_type' => $this->formEntityType,
            'year' => $this->formYear !== '' ? (int) $this->formYear : null,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'amount_remaining' => $amountRemaining,
            'interest_rate' => (float) ($this->formInterestRate ?: 0),
            'start_date' => $this->formStartDate ?: null,
            'due_date' => $this->formDueDate ?: null,
            'status' => $this->formStatus,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->editingId) {
            CreditLine::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            CreditLine::create($data);
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
        Gate::authorize('credit_lines.delete');
        if ($this->editingId) {
            CreditLine::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('credit_lines.delete');
        CreditLine::whereIn('id', $this->selected)->delete();
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    protected function buildQuery()
    {
        $query = CreditLine::query();

        if ($this->search) {
            $query->where('entity_name', 'like', "%{$this->search}%");
        }

        if ($this->filterEntityType) {
            $query->where('entity_type', $this->filterEntityType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterYear) {
            $query->where('year', $this->filterYear);
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

    private function resetForm(): void
    {
        $this->formEntityName = '';
        $this->formEntityType = 'bank';
        $this->formYear = '';
        $this->formTotalAmount = '0';
        $this->formAmountPaid = '0';
        $this->formInterestRate = '0';
        $this->formStartDate = '';
        $this->formDueDate = '';
        $this->formStatus = 'active';
        $this->formNotes = '';
        $this->resetValidation();
    }

    public function render()
    {
        $creditLines = $this->buildQuery()->paginate($this->perPage);
        $totals = CreditLine::selectRaw('SUM(total_amount) as total, SUM(amount_paid) as paid, SUM(amount_remaining) as remaining')->first();

        return view('livewire.credit-lines.credit-line-page', [
            'creditLines' => $creditLines,
            'totals' => $totals,
        ])->layout('layouts.app');
    }
}
