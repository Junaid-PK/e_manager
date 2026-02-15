<?php

namespace App\Livewire\Movements;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Services\CsvImportService;
use App\Services\PdfParserService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class MovementImportWizard extends Component
{
    use WithFileUploads;

    public bool $show = false;
    public string $activeTab = 'csv';
    public int $step = 1;

    public $csvFile;
    public $pdfFile;
    public int $bankAccountId = 0;

    public array $csvHeaders = [];
    public array $csvPreviewRows = [];
    public array $columnMap = [
        'date' => '',
        'value_date' => '',
        'concept' => '',
        'beneficiary' => '',
        'reference' => '',
        'deposit' => '',
        'withdrawal' => '',
        'balance' => '',
    ];

    public array $pdfMovements = [];
    public array $pdfSelected = [];

    public int $importedCount = 0;
    public array $importErrors = [];

    #[On('openImportWizard')]
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
        $this->csvFile = null;
        $this->pdfFile = null;
        $this->csvHeaders = [];
        $this->csvPreviewRows = [];
        $this->columnMap = array_fill_keys(array_keys($this->columnMap), '');
        $this->pdfMovements = [];
        $this->pdfSelected = [];
        $this->importedCount = 0;
        $this->importErrors = [];
        $this->resetValidation();
    }

    public function uploadCsv(): void
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,xlsx,xls,txt', 'bankAccountId' => 'required|exists:bank_accounts,id']);

        $path = $this->csvFile->getRealPath();
        $service = new CsvImportService();
        $result = $service->parseFile($path);

        $this->csvHeaders = $result['headers'];
        $this->csvPreviewRows = array_slice($result['rows'], 0, 10);
        $this->step = 2;
    }

    public function importCsv(): void
    {
        $this->validate(['bankAccountId' => 'required|exists:bank_accounts,id']);

        $path = $this->csvFile->getRealPath();
        $service = new CsvImportService();

        $indexMap = [];
        foreach ($this->columnMap as $field => $headerName) {
            if ($headerName !== '' && $headerName !== null) {
                $index = array_search($headerName, $this->csvHeaders);
                $indexMap[$field] = $index !== false ? $index : null;
            } else {
                $indexMap[$field] = null;
            }
        }

        $result = $service->importMappedData($path, $indexMap, $this->bankAccountId);
        $this->importedCount = $result['imported'];
        $this->importErrors = $result['errors'];
        $this->step = 3;
    }

    public function uploadPdf(): void
    {
        $this->validate(['pdfFile' => 'required|file|mimes:pdf', 'bankAccountId' => 'required|exists:bank_accounts,id']);

        $path = $this->pdfFile->getRealPath();
        $service = new PdfParserService();
        $this->pdfMovements = $service->parseBankStatement($path);
        $this->pdfSelected = array_keys($this->pdfMovements);
        $this->step = 2;
    }

    public function importPdf(): void
    {
        $imported = 0;
        foreach ($this->pdfSelected as $index) {
            if (!isset($this->pdfMovements[$index])) continue;

            $m = $this->pdfMovements[$index];
            BankMovement::create([
                'bank_account_id' => $this->bankAccountId,
                'date' => $m['date'],
                'value_date' => $m['value_date'] ?? null,
                'type' => 'other',
                'concept' => $m['concept'],
                'deposit' => $m['deposit'] ?? null,
                'withdrawal' => $m['withdrawal'] ?? null,
                'balance' => $m['balance'] ?? 0,
                'import_source' => 'pdf',
            ]);
            $imported++;
        }

        $this->importedCount = $imported;
        $this->step = 3;
    }

    public function finish(): void
    {
        $this->dispatch('movementsImported');
        $this->close();
    }

    public function render()
    {
        return view('livewire.movements.movement-import-wizard', [
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(),
        ]);
    }
}
