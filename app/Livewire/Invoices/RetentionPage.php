<?php

namespace App\Livewire\Invoices;

use App\Models\Invoice;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class RetentionPage extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function markRetentionPaid(int $invoiceId): void
    {
        Gate::authorize('invoices.edit');

        $invoice = Invoice::query()
            ->where('retention_amount', '>', 0)
            ->findOrFail($invoiceId);

        if ($invoice->retention_paid_date !== null) {
            return;
        }

        $invoice->update([
            'retention_paid_date' => now()->toDateString(),
        ]);

        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    protected function getInvoices()
    {
        return Invoice::query()
            ->with(['company:id,name', 'client:id,name', 'project:id,name,location'])
            ->where('retention_amount', '>', 0)
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';

                $query->where(function ($nested) use ($search) {
                    $nested->where('invoice_number', 'like', $search)
                        ->orWhere('month', 'like', $search)
                        ->orWhereHas('client', fn ($client) => $client->where('name', 'like', $search))
                        ->orWhereHas('company', fn ($company) => $company->where('name', 'like', $search))
                        ->orWhereHas('project', fn ($project) => $project->where('name', 'like', $search));
                });
            })
            ->orderByRaw('CASE WHEN retention_paid_date IS NULL THEN 0 ELSE 1 END')
            ->orderBy('date_due')
            ->orderByDesc('date_issued')
            ->paginate(25);
    }

    protected function getStats(): array
    {
        $baseQuery = Invoice::query()
            ->where('retention_amount', '>', 0)
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';

                $query->where(function ($nested) use ($search) {
                    $nested->where('invoice_number', 'like', $search)
                        ->orWhere('month', 'like', $search)
                        ->orWhereHas('client', fn ($client) => $client->where('name', 'like', $search))
                        ->orWhereHas('company', fn ($company) => $company->where('name', 'like', $search))
                        ->orWhereHas('project', fn ($project) => $project->where('name', 'like', $search));
                });
            });

        return [
            'invoice_count' => (clone $baseQuery)->count(),
            'retention_total' => (float) (clone $baseQuery)->sum('retention_amount'),
            'retention_paid_total' => (float) (clone $baseQuery)->whereNotNull('retention_paid_date')->sum('retention_amount'),
            'retention_pending_total' => (float) (clone $baseQuery)->whereNull('retention_paid_date')->sum('retention_amount'),
        ];
    }

    public function render()
    {
        Gate::authorize('invoices.retention');

        return view('livewire.invoices.retention-page', [
            'invoices' => $this->getInvoices(),
            'stats' => $this->getStats(),
        ])->layout('layouts.app');
    }
}
