<?php

namespace App\Livewire\Invoices;

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

    public bool $partialOnly = false;

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

    public function updatedPartialOnly(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->partialOnly = false;
        $this->resetPage();
    }

    protected function baseQuery()
    {
        $query = Invoice::query()
            ->where('amount_paid', '>', 0)
            ->whereNotNull('paid_date');

        if ($this->partialOnly) {
            $query->whereColumn('amount_paid', '<', 'total');
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($this->dateFrom) {
            $query->where('paid_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('paid_date', '<=', $this->dateTo);
        }

        return $query;
    }

    protected function getGroupedInvoices()
    {
        $invoices = $this->baseQuery()
            ->with(['company:id,name', 'client:id,name'])
            ->orderBy('paid_date', 'desc')
            ->orderBy('invoice_number')
            ->get();

        return $invoices->groupBy(function ($invoice) {
            return $invoice->paid_date->format('Y-m-d');
        });
    }

    protected function getStats(): array
    {
        $row = $this->baseQuery()
            ->toBase()
            ->selectRaw(
                'COUNT(*) as invoice_count, '.
                'COALESCE(SUM(total), 0) as total_sum, '.
                'COALESCE(SUM(amount_paid), 0) as amount_paid_sum, '.
                'COALESCE(SUM(amount_remaining), 0) as amount_remaining_sum'
            )
            ->first();

        return [
            'invoice_count' => (int) ($row->invoice_count ?? 0),
            'total_sum' => (float) ($row->total_sum ?? 0),
            'amount_paid_sum' => (float) ($row->amount_paid_sum ?? 0),
            'amount_remaining_sum' => (float) ($row->amount_remaining_sum ?? 0),
        ];
    }

    public function render()
    {
        Gate::authorize('invoices.payment_summary');

        return view('livewire.invoices.payment-summary-page', [
            'groupedInvoices' => $this->getGroupedInvoices(),
            'stats' => $this->getStats(),
        ])->layout('layouts.app');
    }
}
