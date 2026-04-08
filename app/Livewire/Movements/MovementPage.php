<?php

namespace App\Livewire\Movements;

use App\Exports\MovementExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Invoice;
use App\Models\MovementCategory;
use App\Models\MovementType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class MovementPage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public bool $showCategoryModal = false;

    public string $bulkCategory = '';

    #[\Livewire\Attributes\Url(as: 'bank_account_id')]
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

    public function mount(): void
    {
        // Defaults (cannot override WithSorting $sortField/$sortDirection in the class body — PHP trait conflict).
        if ($this->sortField === '') {
            $this->sortField = 'date';
            $this->sortDirection = 'desc';
        }
    }

    protected function rules(): array
    {
        return [
            'formBankAccountId' => 'required|exists:bank_accounts,id',
            'formDate' => 'required|date',
            'formValueDate' => 'nullable|date',
            'formType' => 'required|string|max:100',
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
        $this->filterCategory = '';
        $this->resetPage();
    }

    public function updatedFormType(): void
    {
        $this->formCategory = '';
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

    public function exportToExcel()
    {
        Gate::authorize('movements.export');
        $movements = $this->buildQuery()->get();
        $filename = 'movements-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new MovementExport($movements), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
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
        if ($this->editingId) {
            Gate::authorize('movements.edit');
        } else {
            Gate::authorize('movements.create');
        }

        $this->validate();

        $resolvedCategory = $this->resolveCategoryForType(trim($this->formCategory ?? ''), $this->formType);

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
            'category' => $resolvedCategory['category'],
            'notes' => $this->formNotes ?: null,
        ];
        if ($resolvedCategory['deposit'] !== null) {
            $data['deposit'] = $resolvedCategory['deposit'];
            $data['withdrawal'] = null;
        }

        if ($this->editingId) {
            $movement = BankMovement::findOrFail($this->editingId);
            $movement->update($data);
            if ($resolvedCategory['invoice_id'] !== null) {
                $invoice = Invoice::findOrFail($resolvedCategory['invoice_id']);
                $pool = (float) ($data['deposit'] ?? 0);
                $this->syncInvoicesForBillPayment(collect([$invoice]), $pool);
            }
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            $data['import_source'] = 'manual';
            $created = BankMovement::create($data);
            if ($resolvedCategory['invoice_id'] !== null) {
                $invoice = Invoice::findOrFail($resolvedCategory['invoice_id']);
                $pool = (float) ($data['deposit'] ?? 0);
                $this->syncInvoicesForBillPayment(collect([$invoice]), $pool);
            }
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
        Gate::authorize('movements.delete');
        if ($this->editingId) {
            BankMovement::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('movements.delete');
        BankMovement::whereIn('id', $this->selected)->delete();
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function quickUpdateType(int $id, string $type): void
    {
        $slug = $this->resolveOrCreateMovementType(trim($type));
        BankMovement::findOrFail($id)->update(['type' => $slug]);
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function quickUpdateCategory(int $id, string $category): void
    {
        $movement = BankMovement::findOrFail($id);
        $input = trim($category ?? '');
        if ($movement->type === 'bill') {
            $invoiceTokens = [];
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                $invoiceTokens = collect($decoded)->filter(fn ($v) => is_string($v) && str_starts_with($v, 'invoice:'))->values()->all();
            } elseif (str_starts_with($input, 'invoice:')) {
                $invoiceTokens = [$input];
            }
            $invoiceIds = collect($invoiceTokens)
                ->map(fn ($token) => (int) substr($token, 8))
                ->filter(fn ($n) => $n > 0)
                ->unique()
                ->values()
                ->all();
            if (count($invoiceIds) > 0) {
                $invoices = Invoice::with('client')->whereIn('id', $invoiceIds)->get();
                $suggestedDeposit = $invoices->sum(function (Invoice $invoice) {
                    return max(0, round((float) $invoice->total - (float) $invoice->amount_paid, 2));
                });
                $existingDeposit = (float) ($movement->deposit ?? 0);
                $paymentPool = $suggestedDeposit <= 0
                    ? 0.0
                    : ($existingDeposit > 0 ? min($suggestedDeposit, $existingDeposit) : $suggestedDeposit);
                $depositColumn = $existingDeposit > 0
                    ? $movement->deposit
                    : ($paymentPool > 0 ? $paymentPool : null);
                $categoryLabel = $invoices
                    ->map(fn (Invoice $invoice) => trim(($invoice->invoice_number ?? '').' - '.($invoice->client?->name ?? '')))
                    ->filter()
                    ->values()
                    ->implode(' | ');
                $movement->update([
                    'category' => $categoryLabel ?: null,
                    'deposit' => $depositColumn,
                    'withdrawal' => null,
                ]);
                $this->syncInvoicesForBillPayment($invoices, $paymentPool);
                $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
                return;
            }
            $movement->update(['category' => null]);
        } else {
            $resolved = $this->resolveOrCreateCategory($input);
            $movement->update(['category' => $resolved]);
        }
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function openCategoryModal(): void
    {
        $this->bulkCategory = '';
        $this->showCategoryModal = true;
    }

    public function applyCategoryToSelected(): void
    {
        $resolved = $this->resolveOrCreateCategory(trim($this->bulkCategory ?? ''));
        BankMovement::whereIn('id', $this->selected)->update(['category' => $resolved]);
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
        $query = BankMovement::with('bankAccount')->select('bank_movements.*')->selectRaw(
            '(SELECT ba.initial_balance FROM bank_accounts ba WHERE ba.id = bank_movements.bank_account_id) + '.
            '(SELECT COALESCE(SUM(COALESCE(m2.deposit,0) - COALESCE(m2.withdrawal,0)), 0) FROM bank_movements m2 '.
            'WHERE m2.bank_account_id = bank_movements.bank_account_id AND (m2.date < bank_movements.date OR (m2.date = bank_movements.date AND m2.id <= bank_movements.id))) as running_balance'
        );

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('concept', 'like', "%{$this->search}%")
                    ->orWhere('beneficiary', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('category', 'like', "%{$this->search}%");
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

    /**
     * Running balance is computed in SQL as: initial_balance + sum(deposit - withdrawal)
     * for all movements on the same account with (date, id) <= (this row). Rows must be
     * ordered chronologically (at least by date + id) for consecutive balances to read
     * sensibly; the trait default used orderBy created_at (latest()), which broke that.
     */
    protected function applySorting($query)
    {
        if ($this->sortField !== '') {
            $query->orderBy($this->sortField, $this->sortDirection);
            if ($this->sortField !== 'id') {
                $query->orderBy('bank_movements.id', $this->sortDirection);
            }

            return $query;
        }

        return $query->orderBy('bank_movements.date', 'desc')->orderBy('bank_movements.id', 'desc');
    }

    protected function getMovements()
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    private function resolveOrCreateCategory(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $existing = MovementCategory::where('name', $value)->first();
        if ($existing) {
            return $existing->name;
        }
        $maxOrder = (int) MovementCategory::max('sort_order');
        MovementCategory::create(['name' => $value, 'sort_order' => $maxOrder + 1]);

        return $value;
    }

    private function resolveOrCreateMovementType(string $value): string
    {
        if ($value === '') {
            return 'transfer';
        }
        $existing = MovementType::where('slug', $value)->orWhere('name', $value)->first();
        if ($existing) {
            return $existing->slug;
        }
        $maxOrder = (int) MovementType::max('sort_order');
        $type = MovementType::create(['name' => $value, 'sort_order' => $maxOrder + 1]);

        return $type->slug;
    }

    /**
     * Allocate the bank deposit (payment pool) across linked invoices: cobrado (amount_paid)
     * increases by each slice; resto (amount_remaining) = total - amount_paid.
     */
    private function syncInvoicesForBillPayment(\Illuminate\Support\Collection $invoices, float $paymentPool): void
    {
        $pool = round(max(0, $paymentPool), 2);
        if ($pool <= 0 || $invoices->isEmpty()) {
            return;
        }

        foreach ($invoices->sortBy('id') as $invoice) {
            if ($invoice->status === 'cancelled') {
                continue;
            }
            $rem = round(max(0, (float) $invoice->total - (float) $invoice->amount_paid), 2);
            if ($rem <= 0) {
                continue;
            }
            $pay = round(min($rem, $pool), 2);
            if ($pay <= 0) {
                continue;
            }
            $pool = round($pool - $pay, 2);
            $newPaid = round((float) $invoice->amount_paid + $pay, 2);
            $newRem = round((float) $invoice->total - $newPaid, 2);
            if ($newRem < 0) {
                $newRem = 0;
            }
            $status = 'pending';
            if ($newRem <= 0.00001) {
                $status = 'paid';
            } elseif ($newPaid > 0) {
                $status = 'partial';
            }
            Invoice::whereKey($invoice->id)->update([
                'amount_paid' => $newPaid,
                'amount_remaining' => $newRem,
                'status' => $status,
            ]);
            if ($pool <= 0) {
                break;
            }
        }
    }

    private function resolveCategoryForType(string $input, string $type): array
    {
        if ($input === '') {
            return ['category' => null, 'deposit' => null, 'invoice_id' => null];
        }
        if ($type === 'bill' && str_starts_with($input, 'invoice:')) {
            $invoiceId = (int) substr($input, 8);
            $invoice = Invoice::with('client')->findOrFail($invoiceId);
            $depositAmount = (float) ($invoice->amount_remaining ?? 0) > 0
                ? (float) $invoice->amount_remaining
                : (float) $invoice->total;

            return [
                'category' => trim(($invoice->invoice_number ?? '').' - '.($invoice->client?->name ?? '')),
                'deposit' => $depositAmount,
                'invoice_id' => $invoice->id,
            ];
        }

        return [
            'category' => $this->resolveOrCreateCategory($input),
            'deposit' => null,
            'invoice_id' => null,
        ];
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

    /**
     * Book balance per account: initial_balance + sum(deposit − withdrawal), same basis as the table’s running_balance column.
     */
    private function balanceBookRows(): array
    {
        $nets = BankMovement::query()
            ->select('bank_account_id')
            ->selectRaw('COALESCE(SUM(COALESCE(deposit, 0) - COALESCE(withdrawal, 0)), 0) as net')
            ->groupBy('bank_account_id')
            ->pluck('net', 'bank_account_id');

        return BankAccount::query()
            ->orderBy('bank_name')
            ->get()
            ->map(function (BankAccount $ba) use ($nets) {
                $initial = (float) ($ba->initial_balance ?? 0);
                $net = (float) ($nets[$ba->id] ?? 0);

                return [
                    'id' => $ba->id,
                    'name' => $ba->bank_name,
                    'balance' => round($initial + $net, 2),
                ];
            })
            ->values()
            ->all();
    }

    public function render()
    {
        $movements = $this->getMovements();
        $balanceBookRows = $this->balanceBookRows();
        $balanceBookTotal = round(collect($balanceBookRows)->sum('balance'), 2);
        $filterBankId = (int) $this->filterBankAccountId;
        $balanceBookDisplay = $filterBankId > 0
            ? collect($balanceBookRows)->firstWhere('id', $filterBankId)
            : null;
        $needsInvoiceOptions = $this->filterType === 'bill'
            || $this->formType === 'bill'
            || ($movements->count() > 0 && $movements->getCollection()->contains(fn ($m) => $m->type === 'bill'));
        $pendingInvoiceOptions = [];
        if ($needsInvoiceOptions) {
            $pendingInvoiceOptions = Invoice::query()
                ->leftJoin('clients', 'clients.id', '=', 'invoices.client_id')
                ->where(function ($q) {
                    $q->whereIn('invoices.status', ['pending', 'partial'])
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('invoices.amount_remaining')->where('invoices.amount_remaining', '>', 0);
                        });
                })
                ->orderByDesc('invoices.date_due')
                ->orderByDesc('invoices.id')
                ->limit(120)
                ->get([
                    'invoices.id',
                    'invoices.invoice_number',
                    'clients.name as client_name',
                ])
                ->map(function ($invoice) {
                    return [
                        'value' => 'invoice:'.$invoice->id,
                        'label' => trim(($invoice->invoice_number ?? '').' - '.($invoice->client_name ?? '')),
                    ];
                })
                ->values()
                ->all();
        }

        return view('livewire.movements.movement-page', [
            'movements' => $movements,
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(),
            'movementTypes' => MovementType::orderBy('sort_order')->orderBy('name')->get(),
            'movementCategories' => MovementCategory::orderBy('sort_order')->orderBy('name')->get(),
            'pendingInvoiceOptions' => $pendingInvoiceOptions,
            'balanceBookRows' => $balanceBookRows,
            'balanceBookTotal' => $balanceBookTotal,
            'balanceBookDisplay' => $balanceBookDisplay,
        ])->layout('layouts.app');
    }
}
