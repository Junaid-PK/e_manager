<?php

namespace App\Livewire\Invoices;

use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class InvoicePage extends Component
{
    use WithPagination, WithSorting, WithFiltering, WithBulkActions;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showStatusModal = false;
    public ?int $editingId = null;

    public string $filterStatus = '';
    public string $filterCompanyId = '';
    public string $filterClientId = '';
    public string $filterMonth = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public string $formCompanyId = '';
    public string $formClientId = '';
    public string $formProjectId = '';
    public string $formInvoiceNumber = '';
    public string $formMonth = '';
    public string $formDateIssued = '';
    public string $formDateDue = '';
    public string $formAmount = '0';
    public string $formIvaRate = '21';
    public string $formRetentionRate = '0';
    public string $formNotes = '';
    public string $formStatus = 'pending';

    public string $bulkStatus = '';

    protected function rules(): array
    {
        return [
            'formCompanyId' => 'required|exists:companies,id',
            'formClientId' => 'required|exists:clients,id',
            'formProjectId' => 'nullable',
            'formInvoiceNumber' => 'required|string|max:100',
            'formMonth' => 'nullable|string|max:20',
            'formDateIssued' => 'required|date',
            'formDateDue' => 'required|date|after_or_equal:formDateIssued',
            'formAmount' => 'required|numeric|min:0',
            'formIvaRate' => 'required|numeric|min:0|max:100',
            'formRetentionRate' => 'required|numeric|min:0|max:100',
            'formNotes' => 'nullable|string|max:5000',
            'formStatus' => 'required|in:pending,paid,partial,overdue,cancelled',
        ];
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCompanyId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClientId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMonth(): void
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

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $invoice = Invoice::findOrFail($id);
        $this->editingId = $id;
        $this->formCompanyId = (string) $invoice->company_id;
        $this->formClientId = (string) $invoice->client_id;
        $this->formProjectId = (string) ($invoice->project_id ?? '');
        $this->formInvoiceNumber = $invoice->invoice_number;
        $this->formMonth = $invoice->month ?? '';
        $this->formDateIssued = $invoice->date_issued?->format('Y-m-d') ?? '';
        $this->formDateDue = $invoice->date_due?->format('Y-m-d') ?? '';
        $this->formAmount = (string) $invoice->amount;
        $this->formIvaRate = (string) $invoice->iva_rate;
        $this->formRetentionRate = (string) $invoice->retention_rate;
        $this->formNotes = $invoice->notes ?? '';
        $this->formStatus = $invoice->status;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $amount = (float) $this->formAmount;
        $ivaRate = (float) $this->formIvaRate;
        $retentionRate = (float) $this->formRetentionRate;
        $ivaAmount = round($amount * $ivaRate / 100, 2);
        $retentionAmount = round($amount * $retentionRate / 100, 2);
        $total = round($amount + $ivaAmount - $retentionAmount, 2);

        $data = [
            'company_id' => $this->formCompanyId,
            'client_id' => $this->formClientId,
            'project_id' => $this->formProjectId ?: null,
            'invoice_number' => $this->formInvoiceNumber,
            'month' => $this->formMonth ?: null,
            'date_issued' => $this->formDateIssued,
            'date_due' => $this->formDateDue,
            'amount' => $amount,
            'iva_rate' => $ivaRate,
            'iva_amount' => $ivaAmount,
            'retention_rate' => $retentionRate,
            'retention_amount' => $retentionAmount,
            'total' => $total,
            'notes' => $this->formNotes ?: null,
            'status' => $this->formStatus,
        ];

        if ($this->editingId) {
            Invoice::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            Invoice::create($data);
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
            Invoice::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Invoice::whereIn('id', $this->selected)->delete();
        $this->deselectAll();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function duplicate(int $id): void
    {
        $invoice = Invoice::findOrFail($id);
        $this->resetForm();
        $this->editingId = null;
        $this->formCompanyId = (string) $invoice->company_id;
        $this->formClientId = (string) $invoice->client_id;
        $this->formProjectId = (string) ($invoice->project_id ?? '');
        $this->formMonth = $invoice->month ?? '';
        $this->formDateIssued = $invoice->date_issued?->format('Y-m-d') ?? '';
        $this->formDateDue = $invoice->date_due?->format('Y-m-d') ?? '';
        $this->formAmount = (string) $invoice->amount;
        $this->formIvaRate = (string) $invoice->iva_rate;
        $this->formRetentionRate = (string) $invoice->retention_rate;
        $this->formNotes = $invoice->notes ?? '';
        $this->formStatus = 'pending';
        $this->showFormModal = true;
    }

    public function quickStatusUpdate(int $id, string $status): void
    {
        Invoice::findOrFail($id)->update(['status' => $status]);
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function bulkStatusUpdate(): void
    {
        if ($this->bulkStatus && count($this->selected) > 0) {
            Invoice::whereIn('id', $this->selected)->update(['status' => $this->bulkStatus]);
            $this->showStatusModal = false;
            $this->bulkStatus = '';
            $this->deselectAll();
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterCompanyId = '';
        $this->filterClientId = '';
        $this->filterMonth = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    protected function buildQuery()
    {
        $query = Invoice::query()->with(['company', 'client']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCompanyId) {
            $query->where('company_id', $this->filterCompanyId);
        }

        if ($this->filterClientId) {
            $query->where('client_id', $this->filterClientId);
        }

        if ($this->filterMonth) {
            $query->where('month', 'like', "%{$this->filterMonth}%");
        }

        if ($this->dateFrom) {
            $query->where('date_issued', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('date_issued', '<=', $this->dateTo);
        }

        return $this->applySorting($query);
    }

    protected function getPageItemIds(): array
    {
        return $this->buildQuery()->paginate($this->perPage)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->buildQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function getInvoices()
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    private function resetForm(): void
    {
        $this->formCompanyId = '';
        $this->formClientId = '';
        $this->formProjectId = '';
        $this->formInvoiceNumber = '';
        $this->formMonth = '';
        $this->formDateIssued = '';
        $this->formDateDue = '';
        $this->formAmount = '0';
        $this->formIvaRate = '21';
        $this->formRetentionRate = '0';
        $this->formNotes = '';
        $this->formStatus = 'pending';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.invoices.invoice-page', [
            'invoices' => $this->getInvoices(),
            'companies' => Company::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
            'projects' => $this->formCompanyId
                ? Project::where('company_id', $this->formCompanyId)->orderBy('name')->get()
                : collect(),
        ])->layout('layouts.app');
    }
}
