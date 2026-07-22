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

    public ?int $editingTypeMovementId = null;

    public ?int $editingCategoryMovementId = null;

    public string $inlineBillType = '';

    public string $invoiceSelectionMode = 'bill';

    public string $bulkCategory = '';

    public string $bulkType = '';

    #[\Livewire\Attributes\Url(as: 'bank_account_id')]
    public string $filterBankAccountId = '';

    public string $filterType = '';

    public string $filterDirection = 'all';

    public string $filterCategory = '';

    public bool $filterUnassigned = false;

    public string $filterUserId = '';

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

        $this->filterUserId = $this->canAccessAllMovements() ? '' : (string) auth()->id();
    }

    private function canAccessAllMovements(): bool
    {
        return (bool) auth()->user()?->isAdmin() || Gate::allows('movements.access_all');
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

    public function updatedFilterUserId(): void
    {
        $this->filterBankAccountId = '';
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
        if ($this->isInvoiceSelectionType($this->formType) && $this->showFormModal) {
            $this->invoiceSelectionMode = $this->invoiceSelectionModeForType($this->formType);
            $this->showBillInvoiceModal = true;
        }
    }

    public function openBillInvoiceModal(): void
    {
        $this->authorizeMovementFormAccess();
        $this->invoiceSelectionMode = $this->invoiceSelectionModeForType($this->formType);
        $this->showBillInvoiceModal = true;
    }

    public function closeBillInvoiceModal(): void
    {
        $this->showBillInvoiceModal = false;
    }

    public function applyInlineBillPayment(): void
    {
        Gate::authorize('movements.edit');

        if ($this->invoiceSelectionMode === 'retention') {
            $this->applyInlineRetentionPayment();

            return;
        }

        if ($this->inlineBillMovementId === null || count($this->selectedInvoiceIds) === 0) {
            $this->resetInlineInvoiceSelection();

            return;
        }

        $movement = BankMovement::findOrFail($this->inlineBillMovementId);
        $originalBankAccountId = (int) $movement->bank_account_id;
        $existingExtra = $this->normalizeMovementExtra($movement->listado_extra);

        if (! empty($existingExtra['bill_payment_allocations'])) {
            $this->reverseInvoicesForBillPayment($existingExtra['bill_payment_allocations']);
        }

        $linkedInvoices = Invoice::with('client')->whereIn('id', $this->selectedInvoiceIds)->get();
        $suggestedDeposit = round($linkedInvoices->sum(fn (Invoice $invoice) => $this->invoiceRemainingDue($invoice)), 2);
        $existingDeposit = (float) ($movement->deposit ?? 0);
        $paymentPool = $suggestedDeposit <= 0
            ? 0.0
            : ($existingDeposit > 0 ? min($suggestedDeposit, $existingDeposit) : $suggestedDeposit);
        $deposit = $existingDeposit > 0 ? $existingDeposit : ($paymentPool > 0 ? $paymentPool : null);

        $movement->update([
            'type' => $this->inlineBillType ?: $this->billMovementTypeSlug(),
            'category' => null,
            'deposit' => $deposit,
            'withdrawal' => null,
            'listado_extra' => $this->buildMovementInvoiceExtra($existingExtra, $linkedInvoices),
        ]);

        $this->logBillPayment('inline_bill_payment.applied', [
            'movement_id' => $movement->id,
            'invoice_ids' => $linkedInvoices->pluck('id')->all(),
            'payment_pool' => $paymentPool,
        ]);

        $allocations = $this->syncInvoicesForBillPayment($linkedInvoices, $paymentPool);
        $movement->update([
            'listado_extra' => $this->buildMovementInvoiceExtra($movement->listado_extra, $linkedInvoices, $allocations),
        ]);
        $this->refreshBalancesForAccounts([$originalBankAccountId, (int) $movement->bank_account_id]);

        $this->resetInlineInvoiceSelection();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    private function applyInlineRetentionPayment(): void
    {
        if ($this->inlineBillMovementId === null || count($this->selectedInvoiceIds) === 0) {
            $this->resetInlineInvoiceSelection();

            return;
        }

        $movement = BankMovement::findOrFail($this->inlineBillMovementId);
        $originalBankAccountId = (int) $movement->bank_account_id;
        $existingExtra = $this->normalizeMovementExtra($movement->listado_extra);

        if (! empty($existingExtra['bill_payment_allocations'])) {
            $this->reverseInvoicesForBillPayment($existingExtra['bill_payment_allocations']);
        }

        $linkedInvoices = Invoice::with('client')
            ->whereIn('id', $this->selectedInvoiceIds)
            ->where('retention_amount', '>', 0)
            ->get();

        if ($linkedInvoices->isEmpty()) {
            $this->resetInlineInvoiceSelection();

            return;
        }

        Invoice::query()
            ->whereIn('id', $linkedInvoices->pluck('id'))
            ->update(['retention_paid_date' => now()->toDateString()]);

        $movement->update([
            'type' => $this->inlineBillType ?: 'retencion',
            'category' => null,
            'listado_extra' => $this->buildMovementInvoiceExtra($existingExtra, $linkedInvoices),
        ]);

        $this->refreshBalancesForAccounts([$originalBankAccountId, (int) $movement->bank_account_id]);
        $this->resetInlineInvoiceSelection();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function toggleInvoiceSelection(int $id): void
    {
        $this->authorizeMovementFormAccess();
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
        $this->authorizeMovementFormAccess();
        $this->selectedInvoiceIds = $this->getPendingInvoiceIds();
    }

    public function deselectAllPendingInvoices(): void
    {
        $this->selectedInvoiceIds = [];
    }

    public function removeSelectedInvoice(int $id): void
    {
        $this->authorizeMovementFormAccess();
        $this->selectedInvoiceIds = array_values(array_filter(
            $this->selectedInvoiceIds,
            fn (int $selectedId): bool => $selectedId !== $id
        ));
    }

    /**
     * @return list<int>
     */
    private function getPendingInvoiceIds(): array
    {
        return $this->invoiceSelectionBaseQuery()
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

    public function updatedFilterUnassigned(): void
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

    public function updatedPerPage(): void
    {
        $this->perPage = $this->normalizedPerPage();
        $this->resetPage();
    }

    private function normalizedPerPage(): int
    {
        $perPage = (int) $this->perPage;

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 100;
    }

    public function setDirection(string $direction): void
    {
        $this->filterDirection = $direction;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterUserId = $this->canAccessAllMovements() ? '' : (string) auth()->id();
        $this->filterBankAccountId = '';
        $this->filterType = '';
        $this->filterDirection = 'all';
        $this->filterCategory = '';
        $this->filterUnassigned = false;
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
        Gate::authorize('movements.create');
        $this->resetForm();
        $this->editingId = null;
        if ($this->filterBankAccountId) {
            $this->formBankAccountId = $this->filterBankAccountId;
        }
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('movements.edit');
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
        $this->selectedInvoiceIds = $this->extractLinkedInvoiceIds($movement);
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

        $isBill = $this->isBillType($this->formType);
        $isRetention = $this->isRetentionType($this->formType);
        $hasInvoices = ($isBill || $isRetention) && count($this->selectedInvoiceIds) > 0;

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
        $movementExtra = null;

        if ($hasInvoices) {
            $linkedInvoices = Invoice::with('client')->whereIn('id', $this->selectedInvoiceIds)->get();
            if ($isBill) {
                $suggestedDeposit = round($linkedInvoices->sum(fn (Invoice $invoice) => $this->invoiceRemainingDue($invoice)), 2);
                $existingDeposit = (float) ($data['deposit'] ?? 0);
                $paymentPool = $suggestedDeposit <= 0
                    ? 0.0
                    : ($existingDeposit > 0 ? min($suggestedDeposit, $existingDeposit) : $suggestedDeposit);
                $data['deposit'] = $existingDeposit > 0 ? $existingDeposit : ($paymentPool > 0 ? $paymentPool : null);
                $data['withdrawal'] = null;
            }
            $data['category'] = null;
            $movementExtra = $this->buildMovementInvoiceExtra(null, $linkedInvoices);
        }

        if ($this->editingId) {
            $movement = BankMovement::findOrFail($this->editingId);
            $originalBankAccountId = (int) $movement->bank_account_id;
            $existingExtra = $this->normalizeMovementExtra($movement->listado_extra);

            if (! empty($existingExtra['bill_payment_allocations'])) {
                $this->reverseInvoicesForBillPayment($existingExtra['bill_payment_allocations']);
            }

            if (! $isBill && ! $isRetention) {
                $movementExtra = $this->buildMovementInvoiceExtra($existingExtra, collect());
            } else {
                $movementExtra = $this->buildMovementInvoiceExtra($existingExtra, $linkedInvoices ?? collect());
            }

            $data['listado_extra'] = $movementExtra;
            $movement->update($data);
            if ($linkedInvoices !== null && $linkedInvoices->count() > 0 && $isBill) {
                $allocations = $this->syncInvoicesForBillPayment($linkedInvoices, $paymentPool);
                $this->logBillPayment('save.update_movement_with_invoices', [
                    'movement_id' => $movement->id,
                    'invoice_ids' => $linkedInvoices->pluck('id')->all(),
                    'payment_pool' => $paymentPool,
                ]);
                $movement->update([
                    'listado_extra' => $this->buildMovementInvoiceExtra($movementExtra, $linkedInvoices, $allocations),
                ]);
            } elseif ($linkedInvoices !== null && $linkedInvoices->count() > 0 && $isRetention) {
                Invoice::query()
                    ->whereIn('id', $linkedInvoices->pluck('id'))
                    ->update(['retention_paid_date' => now()->toDateString()]);
            }
            $this->refreshBalancesForAccounts([$originalBankAccountId, (int) $movement->bank_account_id]);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            $data['import_source'] = 'manual';
            $data['listado_extra'] = ($isBill || $isRetention)
                ? $this->buildMovementInvoiceExtra(null, $linkedInvoices ?? collect())
                : null;
            $created = BankMovement::create($data);
            if ($linkedInvoices !== null && $linkedInvoices->count() > 0 && $isBill) {
                $allocations = $this->syncInvoicesForBillPayment($linkedInvoices, $paymentPool);
                $this->logBillPayment('save.created_movement_with_invoices', [
                    'movement_id' => $created->id,
                    'invoice_ids' => $linkedInvoices->pluck('id')->all(),
                    'payment_pool' => $paymentPool,
                ]);
                $created->update([
                    'listado_extra' => $this->buildMovementInvoiceExtra($created->listado_extra, $linkedInvoices, $allocations),
                ]);
            } elseif ($linkedInvoices !== null && $linkedInvoices->count() > 0 && $isRetention) {
                Invoice::query()
                    ->whereIn('id', $linkedInvoices->pluck('id'))
                    ->update(['retention_paid_date' => now()->toDateString()]);
            }
            $this->refreshBalancesForAccounts([(int) $created->bank_account_id]);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        Gate::authorize('movements.delete');
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
        Gate::authorize('movements.edit');
        $slug = $this->resolveOrCreateMovementType(trim($type));
        $movement = BankMovement::findOrFail($id);
        $previousType = (string) $movement->type;
        $this->editingTypeMovementId = null;

        if ($previousType === $slug) {
            $this->skipRender();

            return;
        }

        if ($this->isBillType($slug)) {
            $this->inlineBillMovementId = $id;
            $this->inlineBillType = $slug;
            $this->invoiceSelectionMode = 'bill';
            $this->selectedInvoiceIds = [];
            $this->showBillInvoiceModal = true;

            return;
        }

        if ($this->isRetentionType($slug)) {
            $this->inlineBillMovementId = $id;
            $this->inlineBillType = $slug;
            $this->invoiceSelectionMode = 'retention';
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
     * Check if a movement type slug represents a bill/invoice payment type.
     * Supports both "bill" (English) and "factura" (Spanish).
     */
    private function isBillType(string $type): bool
    {
        return in_array($type, ['bill', 'factura'], true);
    }

    private function isRetentionType(string $type): bool
    {
        return in_array($type, ['retencion', 'retention'], true);
    }

    private function isInvoiceSelectionType(string $type): bool
    {
        return $this->isBillType($type) || $this->isRetentionType($type);
    }

    private function invoiceSelectionModeForType(string $type): string
    {
        return $this->isRetentionType($type) ? 'retention' : 'bill';
    }

    /**
     * Slug for the "bill" / cobro movement type (matches movement_types.slug, default "bill").
     *
     * @deprecated Use isBillType() to check if a type is a bill type.
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
        Gate::authorize('movements.edit');
        if (is_array($category)) {
            $category = json_encode($category);
        }
        $movement = BankMovement::findOrFail($id);
        $input = trim((string) $category);
        $this->editingCategoryMovementId = null;

        $resolved = $this->resolveOrCreateCategory($input);
        if ((string) ($movement->category ?? '') === (string) ($resolved ?? '')) {
            $this->skipRender();

            return;
        }
        $movement->update(['category' => $resolved]);
        $this->skipRender();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function editInlineType(int $id): void
    {
        Gate::authorize('movements.edit');
        $this->editingTypeMovementId = $id;
        $this->editingCategoryMovementId = null;
    }

    public function editInlineCategory(int $id): void
    {
        Gate::authorize('movements.edit');
        $this->editingCategoryMovementId = $id;
        $this->editingTypeMovementId = null;
    }

    public function openCategoryModal(): void
    {
        Gate::authorize('movements.edit');
        $this->bulkCategory = '';
        $this->bulkType = '';
        $this->showCategoryModal = true;
    }

    public function applyCategoryToSelected(): void
    {
        Gate::authorize('movements.edit');
        $updates = [];

        if (trim($this->bulkCategory ?? '') !== '') {
            $updates['category'] = $this->resolveOrCreateCategory(trim($this->bulkCategory));
        }

        if (trim($this->bulkType ?? '') !== '') {
            $updates['type'] = $this->resolveOrCreateMovementType(trim($this->bulkType));
        }

        if (! empty($updates)) {
            BankMovement::whereIn('id', $this->selected)->update($updates);
        }

        $this->showCategoryModal = false;
        $this->bulkCategory = '';
        $this->bulkType = '';
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    private function authorizeMovementFormAccess(): void
    {
        Gate::authorize(
            $this->editingId !== null || $this->inlineBillMovementId !== null
                ? 'movements.edit'
                : 'movements.create'
        );
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
            ->with(['bankAccount' => function ($query) {
                if ($this->canAccessAllMovements()) {
                    $query->withoutGlobalScope('ownedByUser');
                    auth()->user()->applyDataScope($query, 'movements');
                }

                $query->select(['id', 'bank_name']);
            }])
            ->select('bank_movements.*');

        if ($this->filterUserId !== '') {
            $query->where('bank_movements.user_id', (int) $this->filterUserId);
        }

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

        if ($this->filterUnassigned) {
            $query->where(function ($query) {
                $query->whereNull('type')
                    ->orWhere('type', '')
                    ->orWhere('type', 'other')
                    ->orWhereNull('category')
                    ->orWhere('category', '');
            });
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
        $field = match ($this->sortField) {
            'date' => 'bank_movements.date',
            default => $this->sortField,
        };

        if ($this->sortField !== '') {
            $query->orderBy($field, $this->sortDirection);
            // $query->orderBy('bank_movements.id', $this->sortDirection);

            return $query;
        }

        return $query->orderBy('bank_movements.date', 'desc');
    }

    protected function getMovements()
    {
        $this->perPage = $this->normalizedPerPage();

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
    private function syncInvoicesForBillPayment(\Illuminate\Support\Collection $invoices, float $paymentPool): array
    {
        $pool = round(max(0, $paymentPool), 2);
        $allocations = [];
        $this->logBillPayment('sync_invoices.start', [
            'payment_pool_raw' => $paymentPool,
            'payment_pool_rounded' => $pool,
            'invoice_count' => $invoices->count(),
            'invoice_ids' => $invoices->pluck('id')->values()->all(),
        ]);

        if ($invoices->isEmpty()) {
            $this->logBillPayment('sync_invoices.abort_empty_collection', []);

            return [];
        }

        if ($pool <= 0) {
            $this->logBillPayment('sync_invoices.pool_zero_attempt_status_normalize', [
                'hint' => 'no cash to allocate; if amounts show fully collected, status will be set to paid',
            ]);
            $this->syncInvoiceStatusesWhenNothingLeftDue($invoices);

            return [];
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

            return [];
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
                $previousPaid = round((float) $invoice->amount_paid, 2);
                $newPaid = round((float) $invoice->total, 2);
                Invoice::whereKey($invoice->id)->update([
                    'amount_paid' => $newPaid,
                    'amount_remaining' => 0,
                    'status' => 'paid',
                ]);
                $paidDelta = round(max(0, $newPaid - $previousPaid), 2);
                if ($paidDelta > 0) {
                    $allocations[] = [
                        'invoice_id' => (int) $invoice->id,
                        'amount' => $paidDelta,
                    ];
                }
                $this->logBillPayment('sync_invoices.invoice_updated', [
                    'invoice_id' => $invoice->id,
                    'branch' => 'multi_underpay',
                    'amount_paid' => $newPaid,
                    'amount_remaining' => 0,
                    'status' => 'paid',
                ]);
            }

            return $allocations;
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
            $allocations[] = [
                'invoice_id' => (int) $invoice->id,
                'amount' => $pay,
            ];
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

        return $allocations;
    }

    /**
     * @param  array<int, array{invoice_id:int, amount:float|int|string}>  $allocations
     */
    private function reverseInvoicesForBillPayment(array $allocations): void
    {
        foreach ($allocations as $allocation) {
            $invoiceId = (int) ($allocation['invoice_id'] ?? 0);
            $amount = round(max(0, (float) ($allocation['amount'] ?? 0)), 2);

            if ($invoiceId < 1 || $amount <= 0) {
                continue;
            }

            $invoice = Invoice::find($invoiceId);
            if (! $invoice) {
                continue;
            }

            $newPaid = round(max(0, (float) $invoice->amount_paid - $amount), 2);
            $newRemaining = round(max(0, (float) $invoice->total - $newPaid), 2);
            $status = $newPaid <= 0 ? 'pending' : ($newRemaining <= 0.00001 ? 'paid' : 'partial');

            $invoice->update([
                'amount_paid' => $newPaid,
                'amount_remaining' => $newRemaining,
                'status' => $status,
            ]);
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
        $this->inlineBillType = '';
        $this->invoiceSelectionMode = 'bill';
        $this->resetValidation();
    }

    private function resetInlineInvoiceSelection(): void
    {
        $this->showBillInvoiceModal = false;
        $this->inlineBillMovementId = null;
        $this->inlineBillType = '';
        $this->invoiceSelectionMode = 'bill';
        $this->selectedInvoiceIds = [];
        $this->billInvoiceSearch = '';
    }

    private function normalizeMovementExtra(?array $extra): array
    {
        return is_array($extra) ? $extra : [];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Invoice>  $linkedInvoices
     * @param  array<int, array{invoice_id:int, amount:float|int|string}>  $allocations
     */
    private function buildMovementInvoiceExtra(?array $existingExtra, \Illuminate\Support\Collection $linkedInvoices, array $allocations = []): ?array
    {
        $extra = $this->normalizeMovementExtra($existingExtra);

        unset($extra['linked_invoice_ids'], $extra['linked_invoices'], $extra['bill_payment_allocations']);

        if ($linkedInvoices->isNotEmpty()) {
            $extra['linked_invoice_ids'] = $linkedInvoices->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            $extra['linked_invoices'] = $linkedInvoices
                ->map(fn (Invoice $invoice) => [
                    'id' => (int) $invoice->id,
                    'invoice_number' => (string) ($invoice->invoice_number ?? ''),
                    'client_name' => (string) ($invoice->client?->name ?? ''),
                ])
                ->values()
                ->all();
        }

        if (! empty($allocations)) {
            $extra['bill_payment_allocations'] = array_values(array_filter(array_map(
                fn (array $allocation): ?array => (
                    ((int) ($allocation['invoice_id'] ?? 0)) > 0 && round(max(0, (float) ($allocation['amount'] ?? 0)), 2) > 0
                )
                    ? [
                        'invoice_id' => (int) $allocation['invoice_id'],
                        'amount' => round((float) $allocation['amount'], 2),
                    ]
                    : null,
                $allocations
            )));
        }

        return $extra === [] ? null : $extra;
    }

    /**
     * @return list<int>
     */
    private function extractLinkedInvoiceIds(BankMovement $movement): array
    {
        $extra = $this->normalizeMovementExtra($movement->listado_extra);
        $storedIds = collect($extra['linked_invoice_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($storedIds !== []) {
            return $storedIds;
        }

        if (! $this->isBillType((string) $movement->type) || ! $movement->category) {
            return [];
        }

        $invoiceNumbers = collect(explode('|', (string) $movement->category))
            ->map(fn (string $label) => trim($label))
            ->filter()
            ->map(function (string $label) {
                $parts = preg_split('/\s+-\s+/', $label, 2);

                return trim((string) ($parts[0] ?? $label));
            })
            ->filter()
            ->values()
            ->all();

        if ($invoiceNumbers === []) {
            return [];
        }

        return Invoice::query()
            ->whereIn('invoice_number', $invoiceNumbers)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int|null, invoice_number:string, client_name:string}>
     */
    private function linkedInvoicesForDisplay(BankMovement $movement): array
    {
        $extra = $this->normalizeMovementExtra($movement->listado_extra);
        $stored = collect($extra['linked_invoices'] ?? [])
            ->map(fn (array $item) => [
                'id' => isset($item['id']) ? (int) $item['id'] : null,
                'invoice_number' => (string) ($item['invoice_number'] ?? ''),
                'client_name' => (string) ($item['client_name'] ?? ''),
            ])
            ->filter(fn (array $item) => $item['invoice_number'] !== '' || $item['client_name'] !== '')
            ->values()
            ->all();

        if ($stored !== []) {
            return $stored;
        }

        if (! $this->isBillType((string) $movement->type) || ! $movement->category) {
            return [];
        }

        return collect(explode('|', (string) $movement->category))
            ->map(fn (string $label) => trim($label))
            ->filter()
            ->map(fn (string $label) => [
                'id' => null,
                'invoice_number' => $label,
                'client_name' => '',
            ])
            ->values()
            ->all();
    }

    private function invoiceSelectionBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Invoice::query()
            ->where(function ($q) {
                if ($this->invoiceSelectionMode === 'retention') {
                    $q->where(function ($retentionQuery) {
                        $retentionQuery->where('retention_amount', '>', 0)
                            ->whereNull('retention_paid_date');
                    })->orWhereIn('id', $this->selectedInvoiceIds);

                    return;
                }

                $q->whereIn('status', ['pending', 'partial'])
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('amount_remaining')->where('amount_remaining', '>', 0);
                    })
                    ->orWhereIn('id', $this->selectedInvoiceIds);
            });
    }

    /**
     * Book balance per account: initial_balance + sum(deposit − withdrawal), same basis as the table’s running_balance column.
     */
    private function balanceBookRows(): array
    {
        if ($this->cachedBalanceBookRows !== null) {
            return $this->cachedBalanceBookRows;
        }

        $query = BankAccount::query();

        if ($this->canAccessAllMovements()) {
            $query->withoutGlobalScope('ownedByUser');
            auth()->user()->applyDataScope($query, 'movements');
        }

        if ($this->filterUserId !== '') {
            $query->where('user_id', (int) $this->filterUserId);
        }

        return $this->cachedBalanceBookRows = $query
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

        $pendingInvoices = [];
        if ($this->showBillInvoiceModal) {
            $pendingInvoices = $this->invoiceSelectionBaseQuery()
                ->with('client')
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
                ->get(['id', 'invoice_number', 'total', 'amount_paid', 'amount_remaining', 'retention_amount', 'client_id'])
                ->map(function (Invoice $invoice) {
                    if ($this->invoiceSelectionMode === 'retention') {
                        return (object) [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'client_name' => $invoice->client?->name,
                            'remaining' => round(max(0, (float) $invoice->retention_amount), 2),
                        ];
                    }

                    $remaining = round(max(0, (float) $invoice->total - (float) $invoice->amount_paid), 2);
                    $stored = round(max(0, (float) ($invoice->amount_remaining ?? 0)), 2);

                    return (object) [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_name' => $invoice->client?->name,
                        'remaining' => max($remaining, $stored),
                    ];
                })
                ->values()
                ->all();
        }

        $selectedInvoices = $this->selectedInvoiceIds === []
            ? collect()
            : Invoice::query()
                ->with('client')
                ->whereIn('id', $this->selectedInvoiceIds)
                ->get(['id', 'invoice_number', 'client_id', 'amount_remaining', 'amount_paid', 'total', 'retention_amount'])
                ->sortBy(fn (Invoice $invoice) => array_search((int) $invoice->id, $this->selectedInvoiceIds, true))
                ->values()
                ->map(function (Invoice $invoice) {
                    if ($this->invoiceSelectionMode === 'retention') {
                        return (object) [
                            'id' => (int) $invoice->id,
                            'invoice_number' => (string) $invoice->invoice_number,
                            'client_name' => $invoice->client?->name,
                            'remaining' => round(max(0, (float) $invoice->retention_amount), 2),
                        ];
                    }

                    $remaining = round(max(0, (float) $invoice->total - (float) $invoice->amount_paid), 2);
                    $stored = round(max(0, (float) ($invoice->amount_remaining ?? 0)), 2);

                    return (object) [
                        'id' => (int) $invoice->id,
                        'invoice_number' => (string) $invoice->invoice_number,
                        'client_name' => $invoice->client?->name,
                        'remaining' => max($remaining, $stored),
                    ];
                });

        $movements->getCollection()->transform(function (BankMovement $movement) {
            $movement->linked_invoices_display = $this->linkedInvoicesForDisplay($movement);

            return $movement;
        });

        $canFilterByUser = $this->canAccessAllMovements();
        $bankAccountQuery = BankAccount::query();
        if ($canFilterByUser) {
            $bankAccountQuery->withoutGlobalScope('ownedByUser');
            auth()->user()->applyDataScope($bankAccountQuery, 'movements');
        }
        if ($this->filterUserId !== '') {
            $bankAccountQuery->where('user_id', (int) $this->filterUserId);
        }

        return view('livewire.movements.movement-page', [
            'movements' => $movements,
            'bankAccounts' => $bankAccountQuery->orderBy('bank_name')->get(['id', 'bank_name']),
            'movementUsers' => $canFilterByUser
                ? auth()->user()->accessibleUserQuery('movements')->orderBy('name')->get(['id', 'name'])
                : collect(),
            'canFilterByUser' => $canFilterByUser,
            'movementTypes' => MovementType::orderBy('sort_order')->orderBy('name')->get(),
            'movementCategories' => MovementCategory::orderBy('sort_order')->orderBy('name')->get(),
            'pendingInvoices' => $pendingInvoices,
            'selectedInvoices' => $selectedInvoices,
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
