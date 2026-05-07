<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CsvImportService;
use App\Services\InvoiceImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportNegativeValuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_preserves_trailing_minus_values_as_withdrawals(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = BankAccount::create([
            'bank_name' => 'Test Bank',
            'account_number' => 'PK00TEST0000000001',
            'currency' => 'EUR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $path = $this->createTempCsv([
            ['date', 'concept', 'amount'],
            ['2026-05-01', 'Card payment', '123.45-'],
        ]);

        try {
            $result = app(CsvImportService::class)->importMappedData($path, [
                'date' => 0,
                'concept' => 1,
                'amount' => 2,
            ], $account->id);
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['errors']);

        $movement = BankMovement::query()->firstOrFail();
        $this->assertNull($movement->deposit);
        $this->assertSame('123.45', $movement->withdrawal);
    }

    public function test_invoice_import_preserves_negative_amounts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $path = $this->createTempCsv([
            ['invoice_number', 'company', 'client', 'amount', 'iva_amount', 'retention_amount', 'total', 'amount_paid', 'amount_remaining', 'date_issued', 'date_due'],
            ['CN-001', 'Acme Corp', 'Client One', '-100', '-21', '0', '-121', '0', '-121', '2026-05-01', '2026-05-31'],
        ]);

        try {
            $result = app(InvoiceImportService::class)->importMappedData($path, [
                'invoice_number' => 0,
                'company' => 1,
                'client' => 2,
                'amount' => 3,
                'iva_amount' => 4,
                'retention_amount' => 5,
                'total' => 6,
                'amount_paid' => 7,
                'amount_remaining' => 8,
                'date_issued' => 9,
                'date_due' => 10,
            ]);
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['errors']);

        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame('-100.00', $invoice->amount);
        $this->assertSame('-21.00', $invoice->iva_amount);
        $this->assertSame('-121.00', $invoice->total);
        $this->assertSame('-121.00', $invoice->amount_remaining);
        $this->assertSame('21', (string) $invoice->iva_rate);
    }

    private function createTempCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'e-manager-import-');
        $csvPath = $path.'.csv';
        rename($path, $csvPath);
        $handle = fopen($csvPath, 'wb');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $csvPath;
    }
}
