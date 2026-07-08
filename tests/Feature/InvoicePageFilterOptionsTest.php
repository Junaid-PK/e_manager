<?php

namespace Tests\Feature;

use App\Livewire\Invoices\InvoicePage;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoicePageFilterOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createUserWithInvoiceAccess(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'invoice-filter-test-role']);
        $permissions = Permission::query()
            ->whereIn('name', ['invoices.view', 'invoices.access_all'])
            ->pluck('id');
        $role->permissions()->syncWithoutDetaching($permissions);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    public function test_filter_options_are_built_from_invoice_data(): void
    {
        $user = $this->createUserWithInvoiceAccess();

        $invoiceCompany = Company::factory()->create(['name' => 'Invoice Company']);
        $unusedCompany = Company::factory()->create(['name' => 'Unused Company']);
        $invoiceClient = Client::factory()->create(['name' => 'Invoice Client']);
        $unusedClient = Client::factory()->create(['name' => 'Unused Client']);
        $invoiceProject = Project::create([
            'company_id' => $invoiceCompany->id,
            'name' => 'Invoice Project',
            'status' => 'active',
        ]);
        $unusedProject = Project::create([
            'company_id' => $invoiceCompany->id,
            'name' => 'Unused Project',
            'status' => 'active',
        ]);

        Invoice::factory()->create([
            'company_id' => $invoiceCompany->id,
            'client_id' => $invoiceClient->id,
            'project_id' => $invoiceProject->id,
        ]);

        Livewire::actingAs($user)
            ->test(InvoicePage::class)
            ->assertViewHas('companyFilterOptions', fn (array $options) => collect($options)->pluck('label')->contains('Invoice Company')
                && ! collect($options)->pluck('label')->contains('Unused Company'))
            ->assertViewHas('clientFilterOptions', fn (array $options) => collect($options)->pluck('label')->contains('Invoice Client')
                && ! collect($options)->pluck('label')->contains('Unused Client'))
            ->assertViewHas('projectFilterOptions', fn (array $options) => collect($options)->pluck('label')->contains('Invoice Project')
                && ! collect($options)->pluck('label')->contains('Unused Project'));

        $this->assertDatabaseHas('companies', ['id' => $unusedCompany->id]);
        $this->assertDatabaseHas('clients', ['id' => $unusedClient->id]);
        $this->assertDatabaseHas('projects', ['id' => $unusedProject->id]);
    }

    public function test_project_filter_limits_invoice_rows(): void
    {
        $user = $this->createUserWithInvoiceAccess();

        $company = Company::factory()->create();
        $client = Client::factory()->create();
        $visibleProject = Project::create([
            'company_id' => $company->id,
            'name' => 'Visible Project',
            'status' => 'active',
        ]);
        $hiddenProject = Project::create([
            'company_id' => $company->id,
            'name' => 'Hidden Project',
            'status' => 'active',
        ]);

        Invoice::factory()->create([
            'invoice_number' => 'PROJECT-FILTER-IN',
            'company_id' => $company->id,
            'client_id' => $client->id,
            'project_id' => $visibleProject->id,
        ]);
        Invoice::factory()->create([
            'invoice_number' => 'PROJECT-FILTER-OUT',
            'company_id' => $company->id,
            'client_id' => $client->id,
            'project_id' => $hiddenProject->id,
        ]);

        Livewire::actingAs($user)
            ->test(InvoicePage::class)
            ->set('filterProjectId', (string) $visibleProject->id)
            ->assertSee('PROJECT-FILTER-IN')
            ->assertDontSee('PROJECT-FILTER-OUT');
    }
}
