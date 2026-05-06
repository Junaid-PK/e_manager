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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
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
        $this->attachRunningBalances($movements);
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
                $this->logBillPayment('save.update_movement_with_invoice', [
                    'movement_id' => $movement->id,
                    'invoice_id' => $invoice->id,
                    'payment_pool' => $pool,
                ]);
                $this->syncInvoicesForBillPayment(collect([$invoice]), $pool);
            }
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            $data['import_source'] = 'manual';
            $created = BankMovement::create($data);
            if ($resolvedCategory['invoice_id'] !== null) {
                $invoice = Invoice::findOrFail($resolvedCategory['invoice_id']);
                $pool = (float) ($data['deposit'] ?? 0);
                $this->logBillPayment('save.created_movement_with_invoice', [
                    'movement_id' => $created->id,
                    'invoice_id' => $invoice->id,
                    'payment_pool' => $pool,
                ]);
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

    /**
     * @return list<string> invoice:* tokens from JSON array or a single token
     */
    private function parseInvoiceTokensFromCategoryInput(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            $this->logBillPayment('parse_invoice_tokens.empty_input', []);

            return [];
        }
        $decoded = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $tokens = collect($decoded)
                ->filter(fn ($v) => is_string($v) && str_starts_with($v, 'invoice:'))
                ->values()
                ->all();
            $this->logBillPayment('parse_invoice_tokens.json_array', [
                'decoded_count' => count($decoded),
                'invoice_tokens' => $tokens,
            ]);

            return $tokens;
        }
        if (json_last_error() !== JSON_ERROR_NONE && str_starts_with($input, '[')) {
            $this->logBillPayment('parse_invoice_tokens.json_decode_failed', [
                'json_error' => json_last_error_msg(),
                'input_preview' => mb_substr($input, 0, 200),
            ]);
        }
        if (str_starts_with($input, 'invoice:')) {
            $this->logBillPayment('parse_invoice_tokens.single_token', ['token' => $input]);

            return [$input];
        }

        $this->logBillPayment('parse_invoice_tokens.no_tokens', [
            'input_preview' => mb_substr($input, 0, 200),
        ]);

        return [];
    }

    /**
     * Link invoices to a bill movement: set category label, deposit, sync invoice paid/status.
     * Ensures the movement type is "bill" so this still works if this request runs before
     * quickUpdateType has persisted (user chose Bill then applied invoices quickly).
     */
    private function applyBillPaymentForInvoiceTokens(BankMovement $movement, array $invoiceTokens): void
    {
        $this->logBillPayment('apply_bill_tokens.start', [
            'movement_id' => $movement->id,
            'movement_type_before' => $movement->type,
            'invoice_tokens' => $invoiceTokens,
        ]);

        $invoiceIds = collect($invoiceTokens)
            ->map(fn (string $token) => (int) substr($token, 8))
            ->filter(fn ($n) => $n > 0)
            ->unique()
            ->values()
            ->all();
        if (count($invoiceIds) === 0) {
            $this->logBillPayment('apply_bill_tokens.abort_no_invoice_ids', [
                'movement_id' => $movement->id,
                'invoice_tokens' => $invoiceTokens,
            ]);

            return;
        }

        $billSlug = $this->billMovementTypeSlug();
        if ($movement->type !== $billSlug) {
            $this->logBillPayment('apply_bill_tokens.set_movement_type', [
                'movement_id' => $movement->id,
                'from' => $movement->type,
                'to' => $billSlug,
            ]);
            $movement->update(['type' => $billSlug]);
            $movement->refresh();
        }

        $invoices = Invoice::with('client')->whereIn('id', $invoiceIds)->get();
        $suggestedDeposit = round($invoices->sum(fn (Invoice $invoice) => $this->invoiceRemainingDue($invoice)), 2);
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

        $this->logBillPayment('apply_bill_tokens.amounts', [
            'movement_id' => $movement->id,
            'invoice_ids_requested' => $invoiceIds,
            'invoices_loaded_count' => $invoices->count(),
            'per_invoice' => $invoices->map(fn (Invoice $inv) => [
                'id' => $inv->id,
                'status' => $inv->status,
                'total' => (float) $inv->total,
                'amount_paid' => (float) $inv->amount_paid,
                'amount_remaining_column' => (float) ($inv->amount_remaining ?? 0),
                'remaining_computed' => round(max(0, (float) $inv->total - (float) $inv->amount_paid), 2),
                'remaining_due_used' => $this->invoiceRemainingDue($inv),
            ])->values()->all(),
            'suggested_deposit' => $suggestedDeposit,
            'existing_deposit' => $existingDeposit,
            'payment_pool' => $paymentPool,
            'deposit_column' => $depositColumn,
        ]);

        $movement->update([
            'category' => $categoryLabel ?: null,
            'deposit' => $depositColumn,
            'withdrawal' => null,
        ]);

        $this->logBillPayment('apply_bill_tokens.movement_updated', [
            'movement_id' => $movement->id,
            'category_label' => $categoryLabel,
        ]);

        $this->syncInvoicesForBillPayment($invoices, $paymentPool);
    }

    public function quickUpdateCategory(int $id, string|array $category): void
    {
        $categoryWasArray = is_array($category);
        if (is_array($category)) {
            $category = json_encode($category);
        }
        $movement = BankMovement::findOrFail($id);
        $input = trim((string) $category);
        $this->logBillPayment('quick_update_category.received', [
            'movement_id' => $id,
            'category_param_was_array' => $categoryWasArray,
            'input_length' => strlen($input),
            'input_preview' => mb_substr($input, 0, 300),
        ]);

        $invoiceTokens = $this->parseInvoiceTokensFromCategoryInput($input);

        if (count($invoiceTokens) > 0) {
            $this->applyBillPaymentForInvoiceTokens($movement, $invoiceTokens);
            $this->logBillPayment('quick_update_category.done_invoice_path', ['movement_id' => $id]);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));

            return;
        }

        $billSlug = $this->billMovementTypeSlug();
        if ($movement->type === $billSlug) {
            $this->logBillPayment('quick_update_category.clear_bill_category', [
                'movement_id' => $id,
                'bill_slug' => $billSlug,
            ]);
            $movement->update(['category' => null]);
        } else {
            $resolved = $this->resolveOrCreateCategory($input);
            $this->logBillPayment('quick_update_category.normal_category', [
                'movement_id' => $id,
                'movement_type' => $movement->type,
                'resolved' => $resolved,
            ]);
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
        $movements = $this->buildQuery()->paginate($this->perPage);
        $this->attachRunningBalances($movements->getCollection());

        return $movements;
    }

    private function attachRunningBalances(EloquentCollection $movements): void
    {
        if ($movements->isEmpty()) {
            return;
        }

        $movementIds = $movements->pluck('id')->filter()->values()->all();
        $accountIds = $movements->pluck('bank_account_id')->filter()->unique()->values()->all();

        if ($movementIds === [] || $accountIds === []) {
            return;
        }

        $windowedBalances = DB::table('bank_movements as bm')
            ->join('bank_accounts as ba', 'ba.id', '=', 'bm.bank_account_id')
            ->whereIn('bm.bank_account_id', $accountIds)
            ->select('bm.id')
            ->selectRaw(
                'ba.initial_balance + '.
                'SUM(COALESCE(bm.deposit, 0) - COALESCE(bm.withdrawal, 0)) '.
                'OVER (PARTITION BY bm.bank_account_id ORDER BY bm.date, bm.id) AS running_balance'
            );

        $runningBalances = DB::query()
            ->fromSub($windowedBalances, 'movement_balances')
            ->whereIn('id', $movementIds)
            ->pluck('running_balance', 'id');

        $movements->each(function (BankMovement $movement) use ($runningBalances): void {
            $movement->setAttribute(
                'running_balance',
                round((float) ($runningBalances[$movement->id] ?? $movement->balance ?? 0), 2)
            );
        });
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
            return ['category' => null, 'deposit' => null, 'invoice_id' => null];
        }
        if ($type === $this->billMovementTypeSlug() && str_starts_with($input, 'invoice:')) {
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
        $movements = $this->getMovements();
        $movementTotals = $this->filteredMovementTotals();
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
            'movementTotals' => $movementTotals,
            'balanceBookRows' => $balanceBookRows,
            'balanceBookTotal' => $balanceBookTotal,
            'balanceBookDisplay' => $balanceBookDisplay,
        ])->layout('layouts.app');
    }
}
