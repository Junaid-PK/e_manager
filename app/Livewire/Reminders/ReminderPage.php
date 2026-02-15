<?php

namespace App\Livewire\Reminders;

use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentReminder;
use Livewire\Component;
use Livewire\WithPagination;

class ReminderPage extends Component
{
    use WithPagination, WithSorting, WithFiltering;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;

    public string $filterStatus = 'upcoming';
    public string $filterType = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';

    public string $formRemindableType = 'invoice';
    public string $formRemindableId = '';
    public string $formReminderDate = '';
    public string $formMessage = '';

    protected function rules(): array
    {
        return [
            'formRemindableType' => 'required|in:invoice,expense',
            'formRemindableId' => 'required|integer',
            'formReminderDate' => 'required|date',
            'formMessage' => 'nullable|string|max:1000',
        ];
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
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

    public function updatedFormRemindableType(): void
    {
        $this->formRemindableId = '';
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $reminder = PaymentReminder::findOrFail($id);
        $this->editingId = $id;
        $this->formRemindableType = $reminder->remindable_type === 'App\\Models\\Invoice' ? 'invoice' : 'expense';
        $this->formRemindableId = (string) $reminder->remindable_id;
        $this->formReminderDate = $reminder->reminder_date?->format('Y-m-d') ?? '';
        $this->formMessage = $reminder->message ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $remindableType = $this->formRemindableType === 'invoice'
            ? 'App\\Models\\Invoice'
            : 'App\\Models\\Expense';

        $data = [
            'remindable_type' => $remindableType,
            'remindable_id' => (int) $this->formRemindableId,
            'reminder_date' => $this->formReminderDate,
            'message' => $this->formMessage ?: null,
        ];

        if ($this->editingId) {
            PaymentReminder::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            PaymentReminder::create($data);
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
            PaymentReminder::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function dismiss(int $id): void
    {
        PaymentReminder::findOrFail($id)->update(['is_dismissed' => true]);
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function snooze(int $id, int $days): void
    {
        $reminder = PaymentReminder::findOrFail($id);
        $reminder->update([
            'reminder_date' => $reminder->reminder_date->addDays($days),
        ]);
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function markPaid(int $id): void
    {
        $reminder = PaymentReminder::with('remindable')->findOrFail($id);
        if ($reminder->remindable_type === 'App\\Models\\Invoice') {
            $reminder->remindable->update(['status' => 'paid']);
            $reminder->update(['is_dismissed' => true]);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterStatus = 'all';
        $this->filterType = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    protected function buildQuery()
    {
        $query = PaymentReminder::query()->with('remindable');

        if ($this->search) {
            $search = $this->search;
            $query->where('message', 'like', "%{$search}%");
        }

        match ($this->filterStatus) {
            'upcoming' => $query->upcoming(),
            'overdue' => $query->overdue(),
            'dismissed' => $query->where('is_dismissed', true),
            default => null,
        };

        if ($this->filterType === 'invoice') {
            $query->where('remindable_type', 'App\\Models\\Invoice');
        } elseif ($this->filterType === 'expense') {
            $query->where('remindable_type', 'App\\Models\\Expense');
        }

        if ($this->dateFrom) {
            $query->where('reminder_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('reminder_date', '<=', $this->dateTo);
        }

        return $this->applySorting($query);
    }

    protected function getRemindableItems(): \Illuminate\Support\Collection
    {
        if ($this->formRemindableType === 'invoice') {
            return Invoice::orderBy('invoice_number')->get()->map(fn ($i) => [
                'id' => $i->id,
                'label' => $i->invoice_number . ' — ' . number_format($i->total, 2, ',', '.') . ' €',
            ]);
        }

        return Expense::orderBy('description')->get()->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->description . ' — ' . number_format($e->amount, 2, ',', '.') . ' €',
        ]);
    }

    private function resetForm(): void
    {
        $this->formRemindableType = 'invoice';
        $this->formRemindableId = '';
        $this->formReminderDate = '';
        $this->formMessage = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.reminders.reminder-page', [
            'reminders' => $this->buildQuery()->paginate($this->perPage),
            'remindableItems' => $this->getRemindableItems(),
        ])->layout('layouts.app');
    }
}
