<?php

namespace App\Livewire\Expenses;

use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Company;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ExpensePage extends Component
{
    use WithBulkActions, WithFileUploads, WithFiltering, WithPagination, WithSorting;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showCategoryModal = false;

    public bool $showReceiptPreview = false;

    public ?int $editingId = null;

    public string $previewReceiptUrl = '';

    public string $bulkCategory = '';

    public string $deleteTarget = '';

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

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    // Keep backward compatibility with existing wire actions.
    public function create(): void
    {
        $this->openCreateModal();
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
        if ($this->editingId) {
            Gate::authorize('expenses.edit');
        } else {
            Gate::authorize('expenses.create');
        }

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

    public function confirmDeleteRow(string $kind, int $id): void
    {
        $this->deleteTarget = $kind.':'.$id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deleteTarget === '') {
            return;
        }
        [$kind, $idStr] = explode(':', $this->deleteTarget, 2);
        $id = (int) $idStr;
        if ($kind === 'e') {
            Gate::authorize('expenses.delete');
            Expense::findOrFail($id)->delete();
        } elseif ($kind === 'm') {
            Gate::authorize('movements.delete');
            BankMovement::findOrFail($id)->delete();
        }
        $this->showDeleteModal = false;
        $this->deleteTarget = '';
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function deleteSelected(): void
    {
        $expenseIds = [];
        $movementIds = [];
        foreach ($this->selected as $sel) {
            if (str_starts_with((string) $sel, 'e:')) {
                $expenseIds[] = (int) substr((string) $sel, 2);
            } elseif (str_starts_with((string) $sel, 'm:')) {
                $movementIds[] = (int) substr((string) $sel, 2);
            }
        }
        if ($expenseIds !== []) {
            Gate::authorize('expenses.delete');
            Expense::whereIn('id', $expenseIds)->delete();
        }
        if ($movementIds !== []) {
            Gate::authorize('movements.delete');
            BankMovement::whereIn('id', $movementIds)->delete();
        }
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function bulkCategoryUpdate(): void
    {
        if (! $this->bulkCategory || count($this->selected) === 0) {
            return;
        }
        Gate::authorize('expenses.edit');
        $expenseIds = collect($this->selected)
            ->filter(fn ($s) => str_starts_with((string) $s, 'e:'))
            ->map(fn ($s) => (int) substr((string) $s, 2))
            ->values()
            ->all();
        if ($expenseIds !== []) {
            Expense::whereIn('id', $expenseIds)->update(['category' => $this->bulkCategory]);
        }
        $this->showCategoryModal = false;
        $this->bulkCategory = '';
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function openReceiptPreview(int $id): void
    {
        $expense = Expense::findOrFail($id);
        if ($expense->receipt_path) {
            $this->previewReceiptUrl = Storage::disk('public')->url($expense->receipt_path);
            $this->showReceiptPreview = true;
        }
    }

    public function updateListadoField(string $kind, int $id, string $field, ?string $value): void
    {
        $value = $value ?? '';
        if ($kind === 'm') {
            Gate::authorize('movements.edit');
            $m = BankMovement::findOrFail($id);
            if ($field === 'date') {
                $fallback = $m->date?->format('Y-m-d') ?? now()->format('Y-m-d');
                $m->update(['date' => $value !== '' ? $value : $fallback]);
            } elseif ($field === 'bank_account_id') {
                $baId = (int) $value;
                if ($baId > 0) {
                    $m->update(['bank_account_id' => $baId]);
                }
            } elseif ($field === 'client') {
                $extra = $this->mergeListadoDefaults($m->listado_extra);
                $extra['client'] = $value;
                $m->listado_extra = $extra;
                $m->save();
            } elseif ($field === 'total_amt' || $field === 'total') {
                $amt = $this->parseMoney($value);
                $m->update(['withdrawal' => $amt > 0 ? $amt : null, 'deposit' => null]);
            } elseif ($field === 'value_date') {
                $m->update(['value_date' => $value !== '' ? $value : null]);
            } elseif ($field === 'reference') {
                $m->update(['reference' => $value !== '' ? $value : null]);
            } elseif ($field === 'beneficiary') {
                $m->update(['beneficiary' => $value !== '' ? $value : null]);
            } elseif ($field === 'cif') {
                $extra = $this->mergeListadoDefaults($m->listado_extra);
                $extra['cif'] = $value;
                $m->listado_extra = $extra;
                $m->save();
            } elseif ($field === 'concept') {
                $m->update(['concept' => $value]);
            } elseif (in_array($field, ['bi', 'iva', 'irpf', 'otros'], true)) {
                $this->patchMovementExtra($m, $field, $value);
            }
        } else {
            Gate::authorize('expenses.edit');
            $e = Expense::findOrFail($id);
            if ($field === 'date') {
                $e->update(['date' => $value !== '' ? $value : $e->date->format('Y-m-d')]);
            } elseif ($field === 'bank') {
                $extra = $this->mergeListadoDefaults($e->listado_extra);
                $extra['bank'] = $value;
                $e->listado_extra = $extra;
                $e->save();
            } elseif ($field === 'client') {
                $extra = $this->mergeListadoDefaults($e->listado_extra);
                $extra['client'] = $value;
                $e->listado_extra = $extra;
                $e->save();
            } elseif ($field === 'total_amt' || $field === 'total') {
                $e->update(['amount' => max(0, $this->parseMoney($value))]);
            } elseif ($field === 'invoice_date') {
                $extra = $this->mergeListadoDefaults($e->listado_extra);
                $extra['invoice_date'] = $value !== '' ? $value : null;
                $e->listado_extra = $extra;
                $e->save();
            } elseif ($field === 'invoice_no') {
                $extra = $this->mergeListadoDefaults($e->listado_extra);
                $extra['invoice_no'] = $value;
                $e->listado_extra = $extra;
                $e->save();
            } elseif ($field === 'vendor') {
                $e->update(['vendor' => $value !== '' ? $value : null]);
            } elseif ($field === 'cif') {
                $extra = $this->mergeListadoDefaults($e->listado_extra);
                $extra['cif'] = $value;
                $e->listado_extra = $extra;
                $e->save();
            } elseif ($field === 'description') {
                $e->update(['description' => $value !== '' ? $value : $e->description]);
            } elseif (in_array($field, ['bi', 'iva', 'irpf', 'otros'], true)) {
                $this->patchExpenseExtra($e, $field, $value);
            }
        }
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
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
        return $this->applySorting($this->baseExpenseQuery());
    }

    protected function baseExpenseQuery()
    {
        $query = Expense::query()->with(['company']);

        return $this->applyExpenseFilters($query);
    }

    private function applyExpenseFilters($query)
    {
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

        return $query;
    }

    protected function filteredMovementsQuery()
    {
        $query = BankMovement::query()->with('bankAccount')->whereIn('type', ['buy', 'compra']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('concept', 'like', "%{$search}%")
                    ->orWhere('beneficiary', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($this->filterCategory) {
            $query->where('category', 'like', "%{$this->filterCategory}%");
        }

        if ($this->filterVendor) {
            $query->where('beneficiary', 'like', "%{$this->filterVendor}%");
        }

        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        return $query;
    }

    protected function getMergedRowsCollection(): Collection
    {
        $rows = collect();
        foreach ($this->filteredMovementsQuery()->orderByDesc('date')->orderByDesc('id')->get() as $m) {
            $rows->push($this->mapMovementRow($m));
        }
        foreach ($this->baseExpenseQuery()->orderByDesc('date')->orderByDesc('id')->get() as $e) {
            $rows->push($this->mapExpenseRow($e));
        }

        return $rows->sortByDesc(fn (array $r) => $r['sort_key'])->values();
    }

    protected function getUnifiedPaginator(): LengthAwarePaginator
    {
        $coll = $this->getMergedRowsCollection();
        $perPage = max(1, (int) $this->perPage);
        $page = max(1, (int) $this->getPage('page'));
        $total = $coll->count();
        $slice = $coll->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );
    }

    protected function getPageItemIds(): array
    {
        return $this->getUnifiedPaginator()->getCollection()
            ->map(fn (array $r) => $r['composite'])
            ->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->getMergedRowsCollection()
            ->map(fn (array $r) => $r['composite'])
            ->toArray();
    }

    protected function getCategorySummary(): array
    {
        $query = Expense::query();
        $this->applyExpenseFilters($query);

        return $query->selectRaw('category, SUM(amount) as total_amount')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->pluck('total_amount', 'category')
            ->toArray();
    }

    private function mergeListadoDefaults(?array $extra): array
    {
        return array_merge([
            'client' => '',
            'cif' => '',
            'invoice_date' => null,
            'invoice_no' => '',
            'bank' => '',
            'bi' => null,
            'iva' => null,
            'irpf' => null,
            'otros' => null,
        ], $extra ?? []);
    }

    private function parseMoney(?string $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        $n = str_replace([' ', ','], ['', '.'], trim($v));

        return (float) $n;
    }

    private function formatOptionalMoney(?float $v): string
    {
        if ($v === null) {
            return '';
        }

        return (string) $v;
    }

    private function patchMovementExtra(BankMovement $m, string $key, string $value): void
    {
        $extra = $this->mergeListadoDefaults($m->listado_extra);
        $extra[$key] = $value === '' ? null : $this->parseMoney($value);
        $m->listado_extra = $extra;
        $m->save();
    }

    private function patchExpenseExtra(Expense $e, string $key, string $value): void
    {
        $extra = $this->mergeListadoDefaults($e->listado_extra);
        $extra[$key] = $value === '' ? null : $this->parseMoney($value);
        $e->listado_extra = $extra;
        $e->save();
    }

    private function mapMovementRow(BankMovement $m): array
    {
        $extra = $this->mergeListadoDefaults($m->listado_extra);
        $total = (float) ($m->withdrawal ?: $m->deposit ?: 0);
        $ts = ($m->date ? $m->date->timestamp : 0) * 100000 + $m->id;

        return [
            'composite' => 'm:'.$m->id,
            'kind' => 'm',
            'id' => $m->id,
            'sort_key' => $ts,
            'date' => $m->date?->format('Y-m-d') ?? '',
            'bank_account_id' => $m->bank_account_id,
            'bank_name' => $m->bankAccount?->bank_name ?? '',
            'client' => $extra['client'] !== '' ? $extra['client'] : ($m->beneficiary ?? ''),
            'total_amt' => $this->formatOptionalMoney($total),
            'value_date' => $m->value_date?->format('Y-m-d') ?? ($m->date?->format('Y-m-d') ?? ''),
            'reference' => $m->reference ?? '',
            'beneficiary' => $m->beneficiary ?? '',
            'cif' => $extra['cif'] ?? '',
            'concept' => $m->concept ?? '',
            'bi' => $this->formatOptionalMoney($extra['bi'] !== null ? (float) $extra['bi'] : null),
            'iva' => $this->formatOptionalMoney($extra['iva'] !== null ? (float) $extra['iva'] : null),
            'irpf' => $this->formatOptionalMoney($extra['irpf'] !== null ? (float) $extra['irpf'] : null),
            'otros' => $this->formatOptionalMoney($extra['otros'] !== null ? (float) $extra['otros'] : null),
            'total' => $this->formatOptionalMoney($total),
            'receipt_path' => null,
        ];
    }

    private function mapExpenseRow(Expense $e): array
    {
        $extra = $this->mergeListadoDefaults($e->listado_extra);
        $amt = (float) $e->amount;
        $bank = $extra['bank'] !== '' ? $extra['bank'] : ($e->company?->name ?? '');
        $invoiceDate = $extra['invoice_date'] ?? null;
        if ($invoiceDate && ! is_string($invoiceDate)) {
            $invoiceDate = Carbon::parse($invoiceDate)->format('Y-m-d');
        }
        $ts = ($e->date ? $e->date->timestamp : 0) * 100000 + $e->id;

        return [
            'composite' => 'e:'.$e->id,
            'kind' => 'e',
            'id' => $e->id,
            'sort_key' => $ts,
            'date' => $e->date?->format('Y-m-d') ?? '',
            'bank_account_id' => null,
            'bank_name' => $bank,
            'client' => $extra['client'] ?? '',
            'total_amt' => $this->formatOptionalMoney($amt),
            'value_date' => $invoiceDate ? (string) $invoiceDate : ($e->date?->format('Y-m-d') ?? ''),
            'reference' => $extra['invoice_no'] ?? '',
            'beneficiary' => $e->vendor ?? '',
            'cif' => $extra['cif'] ?? '',
            'concept' => $e->description ?? '',
            'bi' => $this->formatOptionalMoney($extra['bi'] !== null ? (float) $extra['bi'] : null),
            'iva' => $this->formatOptionalMoney($extra['iva'] !== null ? (float) $extra['iva'] : null),
            'irpf' => $this->formatOptionalMoney($extra['irpf'] !== null ? (float) $extra['irpf'] : null),
            'otros' => $this->formatOptionalMoney($extra['otros'] !== null ? (float) $extra['otros'] : null),
            'total' => $this->formatOptionalMoney($amt),
            'receipt_path' => $e->receipt_path,
        ];
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
            'unifiedRows' => $this->getUnifiedPaginator(),
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'categorySummary' => $this->getCategorySummary(),
        ])->layout('layouts.app');
    }
}
