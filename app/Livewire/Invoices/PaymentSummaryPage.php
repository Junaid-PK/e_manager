<?php

namespace App\Livewire\Invoices;

use App\Models\BankMovement;
use App\Models\Invoice;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentSummaryPage extends Component
{
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $search = '';

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    /**
     * @return \Illuminate\Support\Collection<int, BankMovement>
     */
    protected function getMovements()
    {
        $query = BankMovement::query()
            ->whereNotNull('deposit')
            ->where('deposit', '>', 0)
            ->whereNotNull('listado_extra')
            ->whereJsonLength('listado_extra->linked_invoice_ids', '>', 0)
            ->with('bankAccount:id,bank_name');

        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
        }

        $movements = $query->orderBy('date', 'desc')->get();

        $allInvoiceIds = $movements->flatMap(function (BankMovement $movement) {
            return collect($movement->listado_extra['linked_invoice_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0);
        })->unique()->values()->all();

        $invoices = $allInvoiceIds !== []
            ? Invoice::query()
                ->with(['company:id,name', 'client:id,name'])
                ->whereIn('id', $allInvoiceIds)
                ->get()
                ->keyBy('id')
            : collect();

        $movements->each(function (BankMovement $movement) use ($invoices) {
            $ids = collect($movement->listado_extra['linked_invoice_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();
            $linked = $invoices->only($ids)->values();
            $movement->setRelation('linked_invoices', $linked);
            $movement->linked_invoices_total = $linked->sum('total');
            $movement->remaining_amount = max(0, round($movement->linked_invoices_total - (float) $movement->deposit, 2));
        });

        if ($this->search) {
            $search = strtolower($this->search);
            $movements = $movements->filter(function (BankMovement $movement) use ($search) {
                if (str_contains(strtolower((string) $movement->concept), $search)) {
                    return true;
                }

                if (str_contains(strtolower((string) $movement->reference), $search)) {
                    return true;
                }

                if (str_contains(strtolower((string) $movement->bankAccount?->bank_name), $search)) {
                    return true;
                }

                return $movement->linked_invoices->contains(function (Invoice $invoice) use ($search) {
                    return str_contains(strtolower((string) $invoice->invoice_number), $search)
                        || str_contains(strtolower((string) $invoice->company?->name), $search)
                        || str_contains(strtolower((string) $invoice->client?->name), $search);
                });
            })->values();
        }

        return $movements;
    }

    protected function getGroupedMovements()
    {
        return $this->getMovements()->groupBy(function (BankMovement $movement) {
            return $movement->date->format('Y-m-d');
        });
    }

    protected function getStats(): array
    {
        $movements = $this->getMovements();

        $invoiceCount = $movements->sum(fn (BankMovement $m) => $m->linked_invoices->count());
        $movementCount = $movements->count();
        $totalSum = $movements->sum(fn (BankMovement $m) => $m->linked_invoices_total);
        $paidSum = $movements->sum(fn (BankMovement $m) => (float) $m->deposit);
        $remainingSum = max(0, round($totalSum - $paidSum, 2));

        return [
            'invoice_count' => $invoiceCount,
            'movement_count' => $movementCount,
            'total_sum' => $totalSum,
            'amount_paid_sum' => $paidSum,
            'amount_remaining_sum' => $remainingSum,
        ];
    }

    public function render()
    {
        Gate::authorize('invoices.payment_summary');

        return view('livewire.invoices.payment-summary-page', [
            'groupedMovements' => $this->getGroupedMovements(),
            'stats' => $this->getStats(),
        ])->layout('layouts.app');
    }
}
