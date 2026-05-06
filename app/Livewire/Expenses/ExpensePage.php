<?php

namespace App\Livewire\Expenses;

use App\Exports\ExpenseListadoExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCif;
use App\Models\ExpenseProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

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

    public string $filterBankAccountId = '';

    public string $filterUserId = '';

    public string $filterCategory = '';

    public string $filterTrim = '';

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

    public string $formCif = '';

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
            'formCif' => 'nullable|string|max:32',
            'formPaymentMethod' => 'required|in:cash,bank_transfer,card,check,other',
            'formReceipt' => 'nullable|file|max:10240',
            'formRecurring' => 'boolean',
            'formRecurringFrequency' => 'nullable|in:monthly,quarterly,yearly',
            'formNotes' => 'nullable|string|max:5000',
        ];
    }

    public function mount(): void
    {
        $this->filterUserId = auth()->check() ? (string) auth()->id() : '';
    }

    private function canAccessAllExpenses(): bool
    {
        return Gate::allows('expenses.access_all');
    }

    private function expenseQuery()
    {
        $query = Expense::query();

        if ($this->canAccessAllExpenses()) {
            $query->withoutGlobalScope('ownedByUser');
        }

        return $query;
    }

    private function movementQuery()
    {
        $query = BankMovement::query();

        if ($this->canAccessAllExpenses()) {
            $query->withoutGlobalScope('ownedByUser');
        }

        return $query;
    }

    private function companyQuery()
    {
        $query = Company::query();

        if ($this->canAccessAllExpenses()) {
            $query->withoutGlobalScope('ownedByUser');
        }

        return $query;
    }

    private function bankAccountQuery()
    {
        $query = BankAccount::query();

        if ($this->canAccessAllExpenses()) {
            $query->withoutGlobalScope('ownedByUser');
        }

        return $query;
    }

    private function findExpenseForExpensesAccess(int $id): Expense
    {
        return $this->expenseQuery()->findOrFail($id);
    }

    private function findMovementForExpensesAccess(int $id): BankMovement
    {
        return $this->movementQuery()->findOrFail($id);
    }

    public function updatedFilterCompanyId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBankAccountId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUserId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTrim(): void
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

    public function updatedFormVendor(): void
    {
        $name = trim($this->formVendor ?? '');
        if ($name === '') {
            return;
        }
        $p = ExpenseProvider::query()->where('name', $name)->with('cif')->first();
        if ($p?->cif) {
            $this->formCif = $p->cif->code;
        }
    }

    public function addNewExpenseRow(): void
    {
        Gate::authorize('expenses.create');
        $today = now()->format('Y-m-d');
        $category = Expense::CATEGORIES[0] ?? '—';
        $expense = Expense::create([
            'user_id' => auth()->id(),
            'company_id' => null,
            'category' => $category,
            'description' => '—',
            'amount' => 0,
            'date' => $today,
            'vendor' => null,
            'payment_method' => 'cash',
            'recurring' => false,
            'recurring_frequency' => null,
            'notes' => null,
            'listado_extra' => $this->mergeListadoDefaults(null),
            'listado_readonly' => false,
        ]);
        $this->resetPage();
        $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        $eid = $expense->id;
        $this->js("setTimeout(() => document.getElementById('listado-expense-row-{$eid}')?.scrollIntoView({block:'nearest',behavior:'smooth'}), 80)");
    }

    // Keep backward compatibility with existing wire actions.
    public function create(): void
    {
        $this->addNewExpenseRow();
    }

    public function closeExpenseEditModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $expense = $this->findExpenseForExpensesAccess($id);
        if ($expense->listado_readonly) {
            $this->dispatch('notify', type: 'error', message: __('app.expense_readonly'));

            return;
        }
        $this->editingId = $id;
        $this->formCompanyId = (string) ($expense->company_id ?? '');
        $this->formCategory = $expense->category;
        $this->formDescription = $expense->description;
        $this->formAmount = (string) $expense->amount;
        $this->formDate = $expense->date?->format('Y-m-d') ?? '';
        $this->formVendor = $expense->vendor ?? '';
        $extra = $this->mergeListadoDefaults($expense->listado_extra);
        $this->formCif = $extra['cif'] !== '' ? mb_strtoupper((string) $extra['cif']) : '';
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
        Gate::authorize('expenses.edit');
        if (! $this->editingId) {
            return;
        }

        $this->validate();

        $vendorResolved = $this->resolveOrCreateExpenseProvider(trim($this->formVendor ?? ''));
        $provider = $vendorResolved
            ? ExpenseProvider::query()->where('name', $vendorResolved)->with('cif')->first()
            : null;

        $data = [
            'company_id' => $this->formCompanyId ?: null,
            'category' => $this->formCategory,
            'description' => $this->formDescription,
            'amount' => (float) $this->formAmount,
            'date' => $this->formDate,
            'vendor' => $vendorResolved,
            'payment_method' => $this->formPaymentMethod,
            'recurring' => $this->formRecurring,
            'recurring_frequency' => $this->formRecurring ? $this->formRecurringFrequency : null,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->formReceipt) {
            $data['receipt_path'] = $this->formReceipt->store('receipts', 'public');
        }

        $expense = $this->findExpenseForExpensesAccess($this->editingId);
        if ($expense->listado_readonly) {
            return;
        }
        $extra = $this->mergeListadoDefaults($expense->listado_extra);
        $cifTrim = trim($this->formCif ?? '');
        if ($cifTrim !== '') {
            $cifResolved = $this->resolveOrCreateExpenseCif($cifTrim);
        } elseif ($provider?->cif) {
            $cifResolved = $provider->cif->code;
        } else {
            $cifResolved = $this->resolveOrCreateExpenseCif('');
        }
        $extra['cif'] = $cifResolved ?? '';
        $data['listado_extra'] = $extra;
        $expense->update($data);
        $this->linkExpenseProviderToCifFromCodes($vendorResolved, $cifResolved);
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));

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
            $this->findExpenseForExpensesAccess($id)->delete();
        } elseif ($kind === 'm') {
            Gate::authorize('movements.delete');
            $this->findMovementForExpensesAccess($id)->delete();
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
            $this->expenseQuery()->whereIn('id', $expenseIds)->delete();
        }
        if ($movementIds !== []) {
            Gate::authorize('movements.delete');
            $this->movementQuery()->whereIn('id', $movementIds)->delete();
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
            $this->expenseQuery()->whereIn('id', $expenseIds)->where('listado_readonly', false)->update(['category' => $this->bulkCategory]);
        }
        $this->showCategoryModal = false;
        $this->bulkCategory = '';
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function openReceiptPreview(int $id): void
    {
        $expense = $this->findExpenseForExpensesAccess($id);
        if ($expense->receipt_path) {
            $this->previewReceiptUrl = Storage::disk('public')->url($expense->receipt_path);
            $this->showReceiptPreview = true;
        }
    }

    public function updateListadoField(string $kind, int $id, string $field, ?string $value): void
    {
        $value = $value ?? '';
        if ($kind === 'm') {
            if (! Gate::check('movements.edit') && ! Gate::check('expenses.edit')) {
                Gate::authorize('expenses.edit');
            }
            $m = $this->findMovementForExpensesAccess($id);
            if ($field === 'invoice_date') {
                $m->update(['value_date' => $value !== '' ? $value : null]);
            } elseif ($field === 'invoice_no') {
                $m->update(['reference' => $value !== '' ? $value : null]);
            } elseif ($field === 'trim') {
                $this->patchMovementExtra($m, $field, $value);
            } elseif ($field === 'description') {
                $m->update(['concept' => $value !== '' ? $value : null]);
            } elseif ($field === 'total_amt' || $field === 'total') {
                $amt = max(0, $this->parseMoney($value));
                $w = (float) ($m->withdrawal ?? 0);
                $d = (float) ($m->deposit ?? 0);
                if ($d > 0 && $w <= 0) {
                    $m->update(['deposit' => $amt, 'withdrawal' => null]);
                } else {
                    $m->update(['withdrawal' => $amt, 'deposit' => null]);
                }
            } elseif (in_array($field, ['bi', 'iva', 'irpf', 'otros'], true)) {
                $this->patchMovementExtra($m, $field, $value);
            } else {
                return;
            }
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));

            return;
        }

        Gate::authorize('expenses.edit');
        $e = $this->findExpenseForExpensesAccess($id);
        if ($e->listado_readonly) {
            return;
        }
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
        } elseif ($field === 'trim') {
            $this->patchExpenseExtra($e, $field, $value);
        } elseif ($field === 'vendor') {
            $resolved = $value !== '' ? $this->resolveOrCreateExpenseProvider(trim($value)) : null;
            $extra = $this->mergeListadoDefaults($e->listado_extra);
            if ($resolved) {
                $prov = ExpenseProvider::query()->where('name', $resolved)->with('cif')->first();
                if ($prov?->cif) {
                    $extra['cif'] = $prov->cif->code;
                }
            }
            $e->update([
                'vendor' => $resolved,
                'listado_extra' => $extra,
            ]);
        } elseif ($field === 'cif') {
            $resolved = $this->resolveOrCreateExpenseCif(trim($value));
            $extra = $this->mergeListadoDefaults($e->listado_extra);
            $extra['cif'] = $resolved ?? '';
            $e->listado_extra = $extra;
            $e->save();
            $this->linkExpenseProviderToCifFromCodes($e->vendor, $resolved);
        } elseif ($field === 'description') {
            $e->update(['description' => $value !== '' ? $value : $e->description]);
        } elseif (in_array($field, ['bi', 'iva', 'irpf', 'otros'], true)) {
            $this->patchExpenseExtra($e, $field, $value);
        }

        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterCompanyId = '';
        $this->filterBankAccountId = '';
        $this->filterUserId = auth()->check() ? (string) auth()->id() : '';
        $this->filterCategory = '';
        $this->filterTrim = '';
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
        $query = $this->expenseQuery();

        return $this->applyExpenseFilters($query);
    }

    private function applyExpenseFilters($query)
    {
        if ($this->filterUserId !== '') {
            $query->where('user_id', (int) $this->filterUserId);
        }

        if ($this->filterBankAccountId !== '') {
            $bankName = $this->selectedBankName();
            if ($bankName === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('listado_extra->bank', $bankName);
            }
        }

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

        if ($this->filterTrim !== '') {
            $query->where('listado_extra->trim', $this->filterTrim);
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
        $query = $this->movementQuery()->whereIn('type', ['buy', 'compra']);

        if ($this->filterUserId !== '') {
            $query->where('user_id', (int) $this->filterUserId);
        }

        if ($this->filterBankAccountId !== '') {
            $query->where('bank_account_id', (int) $this->filterBankAccountId);
        }

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

        if ($this->filterTrim !== '') {
            $query->where('listado_extra->trim', $this->filterTrim);
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

    protected function unifiedReferenceQuery()
    {
        $movementRefs = (clone $this->filteredMovementsQuery())
            ->reorder()
            ->toBase()
            ->selectRaw("'m' as kind, id, date as sort_date");
        $expenseRefs = (clone $this->baseExpenseQuery())
            ->reorder()
            ->toBase()
            ->selectRaw("'e' as kind, id, date as sort_date");

        return DB::query()
            ->fromSub($movementRefs->unionAll($expenseRefs), 'expense_rows');
    }

    protected function getMergedRowsCollection(): Collection
    {
        return $this->hydrateUnifiedRows(
            $this->unifiedReferenceQuery()
                ->orderByDesc('sort_date')
                ->orderByDesc('id')
                ->get()
        );
    }

    protected function getUnifiedPaginator(): LengthAwarePaginator
    {
        $perPage = max(1, (int) $this->perPage);
        $refs = $this->unifiedReferenceQuery()
            ->orderByDesc('sort_date')
            ->orderByDesc('id')
            ->paginate($perPage, ['kind', 'id', 'sort_date']);

        $refs->setCollection($this->hydrateUnifiedRows($refs->getCollection()));

        return $refs;
    }

    protected function getPageItemIds(): array
    {
        return $this->getUnifiedPaginator()->getCollection()
            ->map(fn (array $r) => $r['composite'])
            ->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->unifiedReferenceQuery()
            ->orderByDesc('sort_date')
            ->orderByDesc('id')
            ->get(['kind', 'id'])
            ->map(fn ($r) => $r->kind.':'.$r->id)
            ->toArray();
    }

    protected function getCategorySummary(): array
    {
        $query = $this->expenseQuery();
        $this->applyExpenseFilters($query);

        return $query->selectRaw('category, SUM(amount) as total_amount')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->pluck('total_amount', 'category')
            ->toArray();
    }

    /**
     * Aggregates for stat cards: same filter rules as the unified listado (expenses + buy/compra movements).
     */
    protected function getListadoStats(): array
    {
        $expenseQ = $this->expenseQuery();
        $this->applyExpenseFilters($expenseQ);
        $expenseTotal = (float) (clone $expenseQ)->sum('amount');
        $expenseCount = (int) (clone $expenseQ)->count();

        $movQ = $this->filteredMovementsQuery();
        $movementTotal = (float) (clone $movQ)->selectRaw(
            'COALESCE(SUM(CASE WHEN COALESCE(withdrawal, 0) != 0 THEN withdrawal ELSE COALESCE(deposit, 0) END), 0) as s'
        )->value('s');
        $movementCount = (int) (clone $movQ)->count();

        return [
            'combined_total' => round($expenseTotal + $movementTotal, 2),
            'expense_total' => round($expenseTotal, 2),
            'movement_total' => round($movementTotal, 2),
            'expense_count' => $expenseCount,
            'movement_count' => $movementCount,
            'row_count' => $expenseCount + $movementCount,
        ];
    }

    public function exportToExcel()
    {
        Gate::authorize('expenses.export');
        $rows = $this->getMergedRowsCollection();
        $filename = 'expenses-listado-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new ExpenseListadoExport($rows), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    private function mergeListadoDefaults(?array $extra): array
    {
        return array_merge([
            'client' => '',
            'cif' => '',
            'invoice_date' => null,
            'invoice_no' => '',
            'trim' => '',
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
        if ($key === 'trim') {
            $extra[$key] = $value;
        } else {
            $extra[$key] = $value === '' ? null : $this->parseMoney($value);
        }
        $m->listado_extra = $extra;
        $m->save();
    }

    private function patchExpenseExtra(Expense $e, string $key, string $value): void
    {
        $extra = $this->mergeListadoDefaults($e->listado_extra);
        if ($key === 'trim') {
            $extra[$key] = $value;
        } else {
            $extra[$key] = $value === '' ? null : $this->parseMoney($value);
        }
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
            'trim' => $extra['trim'] ?? '',
            'beneficiary' => $m->beneficiary ?? '',
            'cif' => ($extra['cif'] ?? '') !== '' ? mb_strtoupper((string) $extra['cif']) : '',
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
            'trim' => $extra['trim'] ?? '',
            'beneficiary' => $e->vendor ?? '',
            'cif' => ($extra['cif'] ?? '') !== '' ? mb_strtoupper((string) $extra['cif']) : '',
            'concept' => $e->description ?? '',
            'bi' => $this->formatOptionalMoney($extra['bi'] !== null ? (float) $extra['bi'] : null),
            'iva' => $this->formatOptionalMoney($extra['iva'] !== null ? (float) $extra['iva'] : null),
            'irpf' => $this->formatOptionalMoney($extra['irpf'] !== null ? (float) $extra['irpf'] : null),
            'otros' => $this->formatOptionalMoney($extra['otros'] !== null ? (float) $extra['otros'] : null),
            'total' => $this->formatOptionalMoney($amt),
            'receipt_path' => $e->receipt_path,
            'listado_readonly' => (bool) $e->listado_readonly,
        ];
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formCompanyId = '';
        $this->formCategory = '';
        $this->formDescription = '';
        $this->formAmount = '0';
        $this->formDate = '';
        $this->formVendor = '';
        $this->formCif = '';
        $this->formPaymentMethod = 'cash';
        $this->formReceipt = null;
        $this->formRecurring = false;
        $this->formRecurringFrequency = 'monthly';
        $this->formNotes = '';
        $this->existingReceiptPath = '';
        $this->resetValidation();
    }

    public function quickUpdateExpenseVendor(int $id, string $value): void
    {
        Gate::authorize('expenses.edit');
        $e = $this->findExpenseForExpensesAccess($id);
        if ($e->listado_readonly) {
            return;
        }
        $resolved = $this->resolveOrCreateExpenseProvider(trim($value));
        $extra = $this->mergeListadoDefaults($e->listado_extra);
        $previousVendor = $e->vendor;
        $previousCif = (string) ($extra['cif'] ?? '');
        $p = $resolved
            ? ExpenseProvider::query()->where('name', $resolved)->with('cif')->first()
            : null;
        if ($p?->cif) {
            $extra['cif'] = $p->cif->code;
        }

        if (($previousVendor ?? null) === $resolved && $previousCif === (string) ($extra['cif'] ?? '')) {
            $this->skipRender();

            return;
        }

        $e->update([
            'vendor' => $resolved,
            'listado_extra' => $extra,
        ]);

        if ($previousCif === (string) ($extra['cif'] ?? '')) {
            $this->skipRender();
        }

        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function quickUpdateMovementBeneficiary(int $id, string $value): void
    {
        if (! Gate::check('movements.edit') && ! Gate::check('expenses.edit')) {
            Gate::authorize('expenses.edit');
        }
        $m = $this->findMovementForExpensesAccess($id);
        $resolved = $value !== '' ? $this->resolveOrCreateExpenseProvider(trim($value)) : null;
        $extra = $this->mergeListadoDefaults($m->listado_extra);
        $previousBeneficiary = $m->beneficiary;
        $previousCif = (string) ($extra['cif'] ?? '');
        if ($resolved) {
            $prov = ExpenseProvider::query()->where('name', $resolved)->with('cif')->first();
            if ($prov?->cif) {
                $extra['cif'] = $prov->cif->code;
            }
        }

        if (($previousBeneficiary ?? null) === $resolved && $previousCif === (string) ($extra['cif'] ?? '')) {
            $this->skipRender();

            return;
        }

        $m->update([
            'beneficiary' => $resolved,
            'listado_extra' => $extra,
        ]);

        if ($previousCif === (string) ($extra['cif'] ?? '')) {
            $this->skipRender();
        }

        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function quickUpdateExpenseCif(int $id, string $value): void
    {
        Gate::authorize('expenses.edit');
        $e = $this->findExpenseForExpensesAccess($id);
        if ($e->listado_readonly) {
            return;
        }
        $resolved = $this->resolveOrCreateExpenseCif(trim($value));
        $extra = $this->mergeListadoDefaults($e->listado_extra);

        if ((string) ($extra['cif'] ?? '') === (string) ($resolved ?? '')) {
            $this->skipRender();

            return;
        }

        $extra['cif'] = $resolved ?? '';
        $e->listado_extra = $extra;
        $e->save();
        $this->linkExpenseProviderToCifFromCodes($e->vendor, $resolved);
        $this->skipRender();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function quickUpdateMovementCif(int $id, string $value): void
    {
        if (! Gate::check('movements.edit') && ! Gate::check('expenses.edit')) {
            Gate::authorize('expenses.edit');
        }
        $m = $this->findMovementForExpensesAccess($id);
        $resolved = $this->resolveOrCreateExpenseCif(trim($value));
        $extra = $this->mergeListadoDefaults($m->listado_extra);

        if ((string) ($extra['cif'] ?? '') === (string) ($resolved ?? '')) {
            $this->skipRender();

            return;
        }

        $extra['cif'] = $resolved ?? '';
        $m->listado_extra = $extra;
        $m->save();
        $this->linkExpenseProviderToCifFromCodes($m->beneficiary, $resolved);
        $this->skipRender();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    /**
     * Persist the provider ↔ CIF association when the user enters a CIF next to a vendor/beneficiary name.
     */
    private function linkExpenseProviderToCifFromCodes(?string $providerOrBeneficiaryName, ?string $resolvedCifCode): void
    {
        $name = trim((string) $providerOrBeneficiaryName);
        if ($name === '' || $resolvedCifCode === null || $resolvedCifCode === '') {
            return;
        }
        $code = mb_strtoupper(trim($resolvedCifCode));
        $cif = ExpenseCif::query()->where('code', $code)->first();
        if (! $cif) {
            return;
        }
        ExpenseProvider::query()->where('name', $name)->update(['expense_cif_id' => $cif->id]);
    }

    private function resolveOrCreateExpenseProvider(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $existing = ExpenseProvider::where('name', $value)->first();
        if ($existing) {
            return $existing->name;
        }
        $maxOrder = (int) ExpenseProvider::max('sort_order');
        ExpenseProvider::create(['name' => $value, 'sort_order' => $maxOrder + 1]);

        return $value;
    }

    private function resolveOrCreateExpenseCif(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $normalized = mb_strtoupper($value);
        $existing = ExpenseCif::where('code', $normalized)->first();
        if ($existing) {
            return $existing->code;
        }
        $maxOrder = (int) ExpenseCif::max('sort_order');
        ExpenseCif::create(['code' => $normalized, 'sort_order' => $maxOrder + 1]);

        return $normalized;
    }

    private function expenseProviderSelectOptions(): array
    {
        return ExpenseProvider::query()->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (ExpenseProvider $p) => ['value' => $p->name, 'label' => $p->name])
            ->values()
            ->all();
    }

    private function expenseCifSelectOptions(): array
    {
        return ExpenseCif::query()->orderBy('sort_order')->orderBy('code')->get()
            ->map(fn (ExpenseCif $c) => ['value' => $c->code, 'label' => $c->code])
            ->values()
            ->all();
    }

    private function trimOptions(): array
    {
        return collect(['1T', '2T', '3T', '4T'])
            ->concat(range(1, 12))
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();
    }

    private function selectedBankName(): ?string
    {
        if ($this->filterBankAccountId === '') {
            return null;
        }

        return $this->bankAccountQuery()
            ->whereKey((int) $this->filterBankAccountId)
            ->value('bank_name');
    }

    private function hydrateUnifiedRows(Collection $refs): Collection
    {
        if ($refs->isEmpty()) {
            return collect();
        }

        $expenseIds = $refs->where('kind', 'e')->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $movementIds = $refs->where('kind', 'm')->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $expenses = $expenseIds === []
            ? new EloquentCollection()
            : $this->expenseQuery()
                ->with([
                    'company' => fn ($q) => $this->canAccessAllExpenses() ? $q->withoutGlobalScope('ownedByUser') : $q,
                ])
                ->whereIn('id', $expenseIds)
                ->get()
                ->keyBy('id');

        $movements = $movementIds === []
            ? new EloquentCollection()
            : $this->movementQuery()
                ->with([
                    'bankAccount' => fn ($q) => $this->canAccessAllExpenses() ? $q->withoutGlobalScope('ownedByUser') : $q,
                ])
                ->whereIn('id', $movementIds)
                ->get()
                ->keyBy('id');

        return $refs->map(function ($ref) use ($expenses, $movements) {
            if ($ref->kind === 'e') {
                $expense = $expenses->get((int) $ref->id);

                return $expense ? $this->mapExpenseRow($expense) : null;
            }

            $movement = $movements->get((int) $ref->id);

            return $movement ? $this->mapMovementRow($movement) : null;
        })->filter()->values();
    }

    public function render()
    {
        $userOptions = $this->canAccessAllExpenses()
            ? User::query()->orderBy('name')->get()
            : User::query()->whereKey(auth()->id())->get();

        return view('livewire.expenses.expense-page', [
            'unifiedRows' => $this->getUnifiedPaginator(),
            'bankAccounts' => $this->bankAccountQuery()->orderBy('bank_name')->get(),
            'companies' => $this->companyQuery()->orderBy('name')->get(),
            'expenseUsers' => $userOptions,
            'categorySummary' => $this->getCategorySummary(),
            'listadoStats' => $this->getListadoStats(),
            'expenseProviderOpts' => $this->expenseProviderSelectOptions(),
            'expenseCifOpts' => $this->expenseCifSelectOptions(),
            'trimOptions' => $this->trimOptions(),
        ])->layout('layouts.app');
    }
}
