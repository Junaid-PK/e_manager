<?php

namespace App\Livewire\Invoices;

use App\Services\InvoiceImportService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class InvoiceImportWizard extends Component
{
    use WithFileUploads;

    public bool $show = false;
    public int $step = 1;

    public $file;

    public array $headers = [];
    public array $previewRows = [];
    public array $columnMap = [
        'company' => '',
        'client' => '',
        'invoice_number' => '',
        'month' => '',
        'date_issued' => '',
        'date_due' => '',
        'amount' => '',
        'iva_amount' => '',
        'retention_amount' => '',
        'total' => '',
        'status' => '',
        'payment_type' => '',
        'amount_paid' => '',
        'amount_remaining' => '',
        'bank_date' => '',
        'bank_name' => '',
    ];

    public int $importedCount = 0;
    public array $importErrors = [];

    #[On('openInvoiceImportWizard')]
    public function open(): void
    {
        $this->show = true;
        $this->resetState();
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetState();
    }

    private function resetState(): void
    {
        $this->step = 1;
        $this->file = null;
        $this->headers = [];
        $this->previewRows = [];
        $this->columnMap = array_fill_keys(array_keys($this->columnMap), '');
        $this->importedCount = 0;
        $this->importErrors = [];
        $this->resetValidation();
    }

    public function processUpload(): void
    {
        $this->validate(['file' => 'required|file|mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream']);

        $path = $this->file->getRealPath();
        $service = new InvoiceImportService();
        $result = $service->parseFile($path);

        $this->headers = $result['headers'];
        $this->previewRows = array_slice($result['rows'], 0, 10);
        $this->autoMapColumns();
        $this->step = 2;
    }

    private function autoMapColumns(): void
    {
        $keywords = [
            'company' => ['empresa', 'company', 'compañia'],
            'client' => ['cliente', 'client'],
            'invoice_number' => ['factura', 'invoice', 'nº', 'numero'],
            'month' => ['month', 'mes'],
            'date_issued' => ['fecha', 'date', 'issued'],
            'date_due' => ['vencimiento', 'due'],
            'amount' => ['actual', 'importe', 'base', 'amount'],
            'iva_amount' => ['iva'],
            'retention_amount' => ['ret', 'retencion'],
            'total' => ['total'],
            'status' => ['status', 'estado'],
            'payment_type' => ['type', 'tipo', 'pago'],
            'amount_paid' => ['cobrado', 'paid', 'pagado'],
            'amount_remaining' => ['resto', 'remaining', 'pendiente'],
            'bank_date' => ['fecha banco', 'bank date', 'fecha cobro'],
            'bank_name' => ['banco', 'bank'],
        ];

        foreach ($this->headers as $index => $header) {
            $headerLower = strtolower($header);
            foreach ($keywords as $field => $terms) {
                if ($this->columnMap[$field] !== '') continue;
                foreach ($terms as $term) {
                    if (str_contains($headerLower, $term)) {
                        $this->columnMap[$field] = (string) $index;
                        break 2;
                    }
                }
            }
        }
    }

    public function import(): void
    {
        $path = $this->file->getRealPath();
        $service = new InvoiceImportService();

        $indexMap = [];
        foreach ($this->columnMap as $field => $headerIndex) {
            $indexMap[$field] = ($headerIndex !== '' && $headerIndex !== null) ? (int) $headerIndex : null;
        }

        $result = $service->importMappedData($path, $indexMap);
        $this->importedCount = $result['imported'];
        $this->importErrors = $result['errors'];
        $this->step = 3;
    }

    public function finish(): void
    {
        $this->dispatch('invoicesImported');
        $this->close();
    }

    public function render()
    {
        return view('livewire.invoices.invoice-import-wizard');
    }
}
