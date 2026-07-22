<?php

namespace Tests\Feature;

use App\Livewire\Invoices\InvoiceImportWizard;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\InvoiceImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceSyncImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_existing_invoices_creates_new_ones_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['name' => 'MON2026']);
        $client = Client::create(['name' => 'Recop Restauracions']);
        $oldProject = Project::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'name' => 'Old project',
            'status' => 'active',
        ]);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'project_id' => $oldProject->id,
            'invoice_number' => '26/F-296',
            'month' => 'May-2026',
            'date_issued' => '2026-05-01',
            'date_due' => '2026-06-01',
            'amount' => 500,
            'iva_amount' => 105,
            'total' => 605,
            'amount_paid' => 605,
            'amount_remaining' => 0,
            'status' => 'paid',
        ]);

        $path = $this->createTempCsv([
            ['EMPRESA', 'ID', 'CLIENTE', 'MES', 'FACTURA Nº', 'ACTUAL', 'IVA', 'RET', 'TOTAL', 'ESTADO'],
            ['MON2026', '32627 - Façana Plaça', 'Recop Restauracions', 'Jun-2026', ' 26/F-296 ', '1.120,00 €', '235,20 €', '0', '1.355,20 €', 'pending'],
            ['MON2026', '32628 - Reforma', 'Recop Restauracions', 'Jun-2026', '26/F-297', '2.000,00 €', '420,00 €', '100,00 €', '2.320,00 €', 'paid'],
        ]);
        $map = [
            'company' => 0,
            'project_id' => 1,
            'client' => 2,
            'month' => 3,
            'invoice_number' => 4,
            'amount' => 5,
            'iva_amount' => 6,
            'retention_amount' => 7,
            'total' => 8,
            'status' => 9,
        ];

        try {
            $firstResult = app(InvoiceImportService::class)->syncMappedData($path, $map);
            $secondResult = app(InvoiceImportService::class)->syncMappedData($path, $map);
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, $firstResult['created']);
        $this->assertSame(1, $firstResult['updated']);
        $this->assertSame(0, $firstResult['unchanged']);
        $this->assertSame(0, $secondResult['created']);
        $this->assertSame(0, $secondResult['updated']);
        $this->assertSame(2, $secondResult['unchanged']);
        $this->assertDatabaseCount('invoices', 2);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('1120.00', $invoice->amount);
        $this->assertSame('1355.20', $invoice->total);
        $this->assertSame('605.00', $invoice->amount_paid);
        $this->assertSame('0.00', $invoice->amount_remaining);
        $this->assertSame('2026-06-01', $invoice->date_due?->format('Y-m-d'));
        $this->assertSame('32627 - Façana Plaça', $invoice->project?->name);
        $this->assertSame($client->id, $invoice->project?->client_id);

        $newInvoice = Invoice::query()->where('invoice_number', '26/F-297')->firstOrFail();
        $this->assertSame('pending', $newInvoice->status);
        $this->assertSame('32628 - Reforma', $newInvoice->project?->name);
        $this->assertSame($newInvoice->project_id, Project::query()->where('name', '32628 - Reforma')->value('id'));
    }

    public function test_sync_wizard_maps_id_to_project_and_never_maps_status(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->roles()->attach($adminRole);
        $file = UploadedFile::fake()->createWithContent(
            'invoices.csv',
            "EMPRESA,ID,CLIENTE,FACTURA Nº,ESTADO\nMON2026,Project 1,Client 1,26/F-1,paid\n",
        );

        Livewire::actingAs($user)
            ->test(InvoiceImportWizard::class)
            ->call('open', 'sync')
            ->assertSet('mode', 'sync')
            ->set('file', $file)
            ->call('processUpload')
            ->assertSet('columnMap.project_id', '1')
            ->assertSet('columnMap.status', '');
    }

    private function createTempCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'invoice-sync-').'.csv';
        $handle = fopen($path, 'wb');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $path;
    }
}
