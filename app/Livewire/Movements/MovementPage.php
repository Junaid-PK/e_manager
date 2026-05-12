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
use App\Services\BankMovementBalanceService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

    public bool $showBillInvoiceModal = false;

    public array $selectedInvoiceIds = [];

    public string $billInvoiceSearch = '';

    public ?int $inlineBillMovementId = null;

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

    private ?array $cachedBalanceBookRows = null;

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
        $this->selectedInvoiceIds = [];
        if ($this->formType === $this->billMovementTypeSlug() && $this->showFormModal) {
            $this->showBillInvoiceModal = true;
        }
    }

    public function openBillInvoiceModal(): void
    {
        $this->showBillInvoiceModal = true;
    }

    public function closeBillInvoiceModal(): void
    {
        $this->showBillInvoiceModal = false;
    }

    public function applyInlineBillPayment(): void
    {
        if ($this->inlineBillMovementId === null || count($this->selectedInvoiceIds) === 0) {
            $this->showBillInvoiceModal = false;
            $this->inlineBillMovementId = null;
            $this->selectedInvoiceIds = [];

            return;
        }

        $movement = BankMovement::findOrFail($this->inlineBillMovementId);
        $originalBankAccountId = (int) $movement->bank_account_id;

        $linkedInvoices = Invoice::with('client')->whereIn('id', $this->selectedInvoiceIds)->get();
        $suggestedDeposit = round($linkedInvoices->sum(fn (Invoice $invoice) => $this->invoiceRemainingDue($invoice)), 2);
        $existingDeposit = (float) ($movement->deposit ?? 0);
        $paymentPool = $suggestedDeposit <= 0
            ? 0.0
            : ($existingDeposit > 0 ? min($suggestedDeposit, $existingDeposit) : $suggestedDeposit);
        $deposit = $existingDeposit > 0 ? $existingDeposit : ($paymentPool > 0 ? $paymentPool : null);

        $categoryLabel = $linkedInvoices
            ->map(fn (Invoice $invoice) => trim(($invoice->invoice_number ?? '').' - '.($invoice->client?->name ?? '')))
            ->filter()
            ->values()
            ->implode(' | ');

        $movement->update([
            'type' => $this->billMovementTypeSlug(),
            'category' => $categoryLabel ?: null,
            'deposit' => $deposit,
            'withdrawal' => null,
        ]);

        $this->logBillPayment('inline_bill_payment.applied', [
            'movement_id' => $movement->id,
            'invoice_ids' => $linkedInvoices->pluck('id')->all(),
            'payment_pool' => $paymentPool,
        ]);

        $this->syncInvoicesForBillPayment($linkedInvoices, $paymentPool);
        $this->refreshBalancesForAccounts([$originalBankAccountId, (int) $movement->bank_account_id]);

        $this->showBillInvoiceModal = false;
        $this->inlineBillMovementId = null;
        $this->selectedInvoiceIds = [];
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function toggleInvoiceSelection(int $id): void
    {
        $idx = array_search($id, $this->selectedInvoiceIds, true);
        if ($idx === false) {
            $this->selectedInvoiceIds[] = $id;
        } else {
            unset($this->selectedInvoiceIds[$idx]);
            $this->selectedInvoiceIds = array_values($this->selectedInvoiceIds);
        }
    }

    public function selectAllPendingInvoices(): void
    {
        $this->selectedInvoiceIds = $this->getPendingInvoiceIds();
    }

    public function deselectAllPendingInvoices(): void
    {
        $this->selectedInvoiceIds = [];
    }

    /**
     * @return list<int>
     */
    private function getPendingInvoiceIds(): array
    {
        return Invoice::query()
            ->where(function ($q) {
                $q->whereIn('status', ['pending', 'partial'])
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('amount_remaining')->where('amount_remaining', '>', 0);
                    });
            })
            ->when($this->billInvoiceSearch !== '', function ($q) {
                $q->where(function ($sq) {
                    $sq->where('invoice_number', 'like', "%{$this->billInvoiceSearch}%")
                        ->orWhereHas('client', function ($cq) {
                            $cq->where('name', 'like', "%{$this->billInvoiceSearch}%");
                        });
                });
            })
            ->orderByDesc('date_due')
            ->orderByDesc('id')
            ->limit(120)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
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
        $movements = $this->buildQuery()->get()->each(function (BankMovement $movement): void {
            if (! isset($movement->running_balance) && $movement->balance !== null) {
                $movement->running_balance = $movement->balance;
            }
        });
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

        $isBill = $this->formType === $this->billMovementTypeSlug();
        $hasInvoices = $isBill && count($this->selectedInvoiceIds) > 0;

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

        $linkedInvoices = null;
        $paymentPool = 0.0;

        if ($hasInvoices) {
            $linkedInvoices = Invoice::with('client')->whereIn('id', $this->selectedInvoiceIds)->get();
            $suggestedDeposit = round($linkedInvoices->sum(fn (Invoice $invoice) => $this->invoiceRemainingDue($invoice)), 2);
            $existingDeposit = (float) ($data['deposit'] ?? 0);
            $paymentPool = $suggestedDeposit <= 0
                ? 0.0
                : ($existingDeposit > 0 ? min($suggestedDeposit, $existingDeposit) : $suggestedDeposit);
            $data['deposit'] = $existingDeposit > 0 ? $existingDeposit : ($paymentPool > 0 ? $paymentPool : null);
            $data['withdrawal'] = null;
            $data['category'] = $linkedInvoices
                ->map(fn (Invoice $invoice) => trim(($invoice->invoice_number ?? '').' - '.($invoice->client?->name ?? '')))
                ->filter()
                ->values()
                ->implode(' | ');
            $data['type'] = $this->billMovementTypeSlug();
        }

        if ($this->editingId) {
            $movement = BankMovement::findOrFail($this->editingId);
            $originalBankAccountId = (int) $movement->bank_account_id;
            $movement->update($data);
            if ($linkedInvoices !== null && $linkedInvoices->count() > 0) {
                $this->logBillPayment('save.update_movement_with_invoices', [
                    'movement_id' => $movement->id,
                    'invoice_ids' => $linkedInvoices->pluck('id')->all(),
                    'payment_pool' => $paymentPool,
                ]);
                $this->syncInvoicesForBillPayment($linkedInvoices, $paymentPool);
            }
            $this->refreshBalancesForAccounts([$originalBankAccountId, (int) $movement->bank_account_id]);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            $data['import_source'] = 'manual';
            $created = BankMovement::create($data);
            if ($linkedInvoices !== null && $linkedInvoices->count() > 0) {
                $this->logBillPayment('save.created_movement_with_invoices', [
                    'movement_id' => $created->id,
                    'invoice_ids' => $linkedInvoices->pluck('id')->all(),
                    'payment_pool' => $paymentPool,
                ]);
                $this->syncInvoicesForBillPayment($linkedInvoices, $paymentPool);
            }
            $this->refreshBalancesForAccounts([(int) $created->bank_account_id]);
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
            $movement = BankMovement::findOrFail($this->editingId);
            $accountId = (int) $movement->bank_account_id;
            $movement->delete();
            $this->refreshBalancesForAccounts([$accountId]);
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('movements.delete');
        $accountIds = BankMovement::query()
            ->whereIn('id', $this->selected)
            ->pluck('bank_account_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        BankMovement::whereIn('id', $this->selected)->delete();
        $this->refreshBalancesForAccounts($accountIds);
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function quickUpdateType(int $id, string $type): void
    {
        $slug = $this->resolveOrCreateMovementType(trim($type));
        $movement = BankMovement::findOrFail($id);
        $previousType = (string) $movement->type;

        if ($previousType === $slug) {
            $this->skipRender();

            return;
        }

        if ($slug === $this->billMovementTypeSlug()) {
            $this->inlineBillMovementId = $id;
            $this->selectedInvoiceIds = [];
            $this->showBillInvoiceModal = true;

            return;
        }

        $movement->update(['type' => $slug]);
        $this->skipRender();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    /**
     * Debug trace for bill ↔ invoice linking (grep logs for "movements.bill_payment").
     */
    private function logBillPayment(string $step, array $context = []): void
    {
        Log::info('[movements.bill_payment] '.$step, $context);
    }

    /**
     * Amount still owed on an invoice for bill allocation. Uses both stored amount_remaining
     * and (total − amount_paid) so a stale column does not zero out the pool when the UI still
     * shows a balance (common when imports or edits desynchronise the two).
     */
    private function invoiceRemainingDue(Invoice $invoice): float
    {
        $computed = round(max(0, (float) $invoice->total - (float) $invoice->amount_paid), 2);
        $stored = round(max(0, (float) ($invoice->amount_remaining ?? 0)), 2);

        return round(max($computed, $stored), 2);
    }

    /**
     * When amounts show nothing left to collect but status is not paid yet, align status and columns.
     * Typical after imports or manual edits where total/amount_paid match but status stayed pending/partial.
     */
    private function syncInvoiceStatusesWhenNothingLeftDue(\Illuminate\Support\Collection $invoices): void
    {
        foreach ($invoices as $invoice) {
            $invoice->refresh();
            if ($invoice->status === 'cancelled') {
                continue;
            }
            $due = $this->invoiceRemainingDue($invoice);
            if ($due > 0.01) {
                continue;
            }
            if ($invoice->status === 'paid' && round((float) ($invoice->amount_remaining ?? 0), 2) <= 0.01) {
                continue;
            }
            $total = round((float) $invoice->total, 2);
            $paid = round((float) $invoice->amount_paid, 2);
            $newPaid = max($paid, $total);

            Invoice::whereKey($invoice->id)->update([
                'amount_paid' => $newPaid,
                'amount_remaining' => 0,
                'status' => 'paid',
            ]);
            $this->logBillPayment('sync_invoices.normalize_status_fully_collected', [
                'invoice_id' => $invoice->id,
                'total' => $total,
                'amount_paid_before' => $paid,
                'amount_paid_after' => $newPaid,
            ]);
        }
    }

    /**
     * Slug for the "bill" / cobro movement type (matches movement_types.slug, default "bill").
     */
    private function billMovementTypeSlug(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $row = MovementType::query()->where('slug', 'bill')->first();

        return $cached = $row?->slug ?? 'bill';
    }

    public function quickUpdateCategory(int $id, string|array $category): void
    {
        if (is_array($category)) {
            $category = json_encode($category);
        }
        $movement = BankMovement::findOrFail($id);
        $input = trim((string) $category);

        $resolved = $this->resolveOrCreateCategory($input);
        if ((string) ($movement->category ?? '') === (string) ($resolved ?? '')) {
            $this->skipRender();

            return;
        }
        $movement->update(['category' => $resolved]);
        $this->skipRender();
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
        $query = BankMovement::query()
            ->with(['bankAccount:id,bank_name'])
            ->select('bank_movements.*');

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
        return $this->buildQuery()->simplePaginate($this->perPage);
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
     *
     * When several invoices are linked and the receipt is *less* than the combined amount still
     * due, the shortfall is split in proportion to each invoice’s remaining balance and every
     * linked invoice with a balance is closed as paid (small bank/charges differences absorbed).
     */
    private function syncInvoicesForBillPayment(\Illuminate\Support\Collection $invoices, float $paymentPool): void
    {
        $pool = round(max(0, $paymentPool), 2);
        $this->logBillPayment('sync_invoices.start', [
            'payment_pool_raw' => $paymentPool,
            'payment_pool_rounded' => $pool,
            'invoice_count' => $invoices->count(),
            'invoice_ids' => $invoices->pluck('id')->values()->all(),
        ]);

        if ($invoices->isEmpty()) {
            $this->logBillPayment('sync_invoices.abort_empty_collection', []);

            return;
        }

        if ($pool <= 0) {
            $this->logBillPayment('sync_invoices.pool_zero_attempt_status_normalize', [
                'hint' => 'no cash to allocate; if amounts show fully collected, status will be set to paid',
            ]);
            $this->syncInvoiceStatusesWhenNothingLeftDue($invoices);

            return;
        }

        $eligible = $invoices
            ->filter(fn (Invoice $invoice) => $invoice->status !== 'cancelled')
            ->sortBy('id')
            ->values();

        $remaining = fn (Invoice $invoice): float => $this->invoiceRemainingDue($invoice);

        $withBalance = $eligible->filter(fn (Invoice $inv) => $remaining($inv) > 0)->values();
        $sumRem = round($withBalance->sum(fn (Invoice $inv) => $remaining($inv)), 2);

        if ($sumRem <= 0) {
            $this->logBillPayment('sync_invoices.no_remaining_balance_normalize_status', [
                'eligible_ids' => $eligible->pluck('id')->all(),
                'statuses_before' => $eligible->mapWithKeys(fn (Invoice $i) => [$i->id => $i->status])->all(),
            ]);
            $this->syncInvoiceStatusesWhenNothingLeftDue($eligible);

            return;
        }

        // Two or more invoices with balance, but bank receipt does not cover combined due:
        // split the shortfall proportionally and mark all as paid.
        if ($withBalance->count() >= 2 && $pool < $sumRem - 0.005) {
            $this->logBillPayment('sync_invoices.branch_multi_underpay_close_all_paid', [
                'with_balance_count' => $withBalance->count(),
                'sum_remaining' => $sumRem,
                'pool' => $pool,
            ]);
            foreach ($withBalance as $invoice) {
                $newPaid = round((float) $invoice->total, 2);
                Invoice::whereKey($invoice->id)->update([
                    'amount_paid' => $newPaid,
                    'amount_remaining' => 0,
                    'status' => 'paid',
                ]);
                $this->logBillPayment('sync_invoices.invoice_updated', [
                    'invoice_id' => $invoice->id,
                    'branch' => 'multi_underpay',
                    'amount_paid' => $newPaid,
                    'amount_remaining' => 0,
                    'status' => 'paid',
                ]);
            }

            return;
        }

        $this->logBillPayment('sync_invoices.branch_sequential', [
            'with_balance_count' => $withBalance->count(),
            'sum_remaining' => $sumRem,
            'pool' => $pool,
        ]);

        foreach ($eligible as $invoice) {
            if ($invoice->status === 'cancelled') {
                $this->logBillPayment('sync_invoices.skip_cancelled', ['invoice_id' => $invoice->id]);

                continue;
            }
            $invoice->refresh();
            $rem = $remaining($invoice);
            if ($rem <= 0) {
                $this->logBillPayment('sync_invoices.skip_no_remaining', ['invoice_id' => $invoice->id]);

                continue;
            }
            $pay = round(min($rem, $pool), 2);
            if ($pay <= 0) {
                $this->logBillPayment('sync_invoices.skip_pay_zero', [
                    'invoice_id' => $invoice->id,
                    'rem' => $rem,
                    'pool_left' => $pool,
                ]);

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
            $this->logBillPayment('sync_invoices.invoice_updated', [
                'invoice_id' => $invoice->id,
                'branch' => 'sequential',
                'pay_applied' => $pay,
                'amount_paid' => $newPaid,
                'amount_remaining' => $newRem,
                'status' => $status,
                'pool_after' => $pool,
            ]);
            if ($pool <= 0) {
                $this->logBillPayment('sync_invoices.sequential_pool_exhausted', []);

                break;
            }
        }
    }

    private function resolveCategoryForType(string $input, string $type): array
    {
        if ($input === '') {
            return ['category' => null, 'deposit' => null];
        }

        return [
            'category' => $this->resolveOrCreateCategory($input),
            'deposit' => null,
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
        $this->selectedInvoiceIds = [];
        $this->billInvoiceSearch = '';
        $this->inlineBillMovementId = null;
        $this->resetValidation();
    }

    /**
     * Book balance per account: initial_balance + sum(deposit − withdrawal), same basis as the table’s running_balance column.
     */
    private function balanceBookRows(): array
    {
        if ($this->cachedBalanceBookRows !== null) {
            return $this->cachedBalanceBookRows;
        }

        return $this->cachedBalanceBookRows = BankAccount::query()
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'current_balance'])
            ->map(fn (BankAccount $ba) => [
                'id' => $ba->id,
                'name' => $ba->bank_name,
                'balance' => round((float) ($ba->current_balance ?? 0), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * Totals for the currently filtered movement set, across all filtered rows and not only the current page.
     */
    private function filteredMovementTotals(): array
    {
        $totalsQuery = (clone $this->buildQuery())
            ->reorder()
            ->toBase();
        $totalsQuery->columns = null;
        $totalsQuery->bindings['select'] = [];

        $totals = $totalsQuery
            ->selectRaw('COALESCE(SUM(deposit), 0) as deposit')
            ->selectRaw('COALESCE(SUM(withdrawal), 0) as withdrawal')
            ->first();

        $deposit = round((float) ($totals->deposit ?? 0), 2);
        $withdrawal = round((float) ($totals->withdrawal ?? 0), 2);

        return [
            'deposit' => $deposit,
            'withdrawal' => $withdrawal,
            'balance' => round($deposit - $withdrawal, 2),
        ];
    }

    public function render()
    {
        $this->cachedBalanceBookRows = null;
        $movements = $this->getMovements();
        $movementTotals = $this->filteredMovementTotals();
        $balanceBookRows = $this->balanceBookRows();
        $balanceBookTotal = round(collect($balanceBookRows)->sum('balance'), 2);
        $filterBankId = (int) $this->filterBankAccountId;
        $balanceBookDisplay = $filterBankId > 0
            ? collect($balanceBookRows)->firstWhere('id', $filterBankId)
            : null;

        $pendingInvoices = Invoice::query()
            ->leftJoin('clients', 'clients.id', '=', 'invoices.client_id')
            ->where(function ($q) {
                $q->whereIn('invoices.status', ['pending', 'partial'])
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('invoices.amount_remaining')->where('invoices.amount_remaining', '>', 0);
                    });
            })
            ->when($this->billInvoiceSearch !== '', function ($q) {
                $q->where(function ($sq) {
                    $sq->where('invoices.invoice_number', 'like', "%{$this->billInvoiceSearch}%")
                        ->orWhere('clients.name', 'like', "%{$this->billInvoiceSearch}%");
                });
            })
            ->orderByDesc('invoices.date_due')
            ->orderByDesc('invoices.id')
            ->limit(120)
            ->get([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.total',
                'invoices.amount_paid',
                'invoices.amount_remaining',
                'clients.name as client_name',
            ])
            ->map(function ($invoice) {
                $remaining = round(max(0, (float) $invoice->total - (float) $invoice->amount_paid), 2);
                $stored = round(max(0, (float) ($invoice->amount_remaining ?? 0)), 2);

                return (object) [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client_name,
                    'remaining' => max($remaining, $stored),
                ];
            })
            ->values()
            ->all();

        return view('livewire.movements.movement-page', [
            'movements' => $movements,
            'bankAccounts' => BankAccount::query()->orderBy('bank_name')->get(['id', 'bank_name']),
            'movementTypes' => MovementType::orderBy('sort_order')->orderBy('name')->get(),
            'movementCategories' => MovementCategory::orderBy('sort_order')->orderBy('name')->get(),
            'pendingInvoices' => $pendingInvoices,
            'movementTotals' => $movementTotals,
            'balanceBookRows' => $balanceBookRows,
            'balanceBookTotal' => $balanceBookTotal,
            'balanceBookDisplay' => $balanceBookDisplay,
        ])->layout('layouts.app');
    }

    /**
     * @param  array<int>  $accountIds
     */
    private function refreshBalancesForAccounts(array $accountIds): void
    {
        app(BankMovementBalanceService::class)->recalculateAccounts($accountIds);
        $this->cachedBalanceBookRows = null;
    }
}
