<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createUserWithPermission(string $permission = 'invoices.payment_summary'): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'test-role']);
        $permissionModel = \App\Models\Permission::where('name', $permission)->firstOrFail();
        $accessAllPermission = \App\Models\Permission::where('name', 'invoices.access_all')->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$permissionModel->id, $accessAllPermission->id]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    /**
     * @param  array<int, Invoice>  $invoices
     */
    protected function createMovementWithInvoices(string $date, float $deposit, array $invoices, ?BankAccount $account = null): BankMovement
    {
        $account ??= BankAccount::create([
            'bank_name' => 'Test Bank',
            'account_number' => 'PK00TEST0000000001',
            'currency' => 'EUR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        return BankMovement::create([
            'bank_account_id' => $account->id,
            'date' => $date,
            'type' => 'transfer',
            'concept' => 'Payment',
            'deposit' => $deposit,
            'listado_extra' => [
                'linked_invoice_ids' => collect($invoices)->pluck('id')->all(),
            ],
        ]);
    }

    public function test_page_requires_authentication(): void
    {
        $this->get(route('invoices.payment-summary'))
            ->assertRedirect(route('login'));
    }

    public function test_page_requires_invoices_payment_summary_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'test-viewer']);
        $viewPermission = \App\Models\Permission::where('name', 'invoices.view')->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$viewPermission->id]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->actingAs($user)
            ->get(route('invoices.payment-summary'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_shows_partial_payment_movements_by_default_grouped_by_date(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $invoiceA = Invoice::factory()->create(['total' => 10000]);
        $invoiceB = Invoice::factory()->create(['total' => 5000]);
        $this->createMovementWithInvoices('2026-01-26', 12000, [$invoiceA, $invoiceB]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee('26/01/2026')
            ->assertSee((string) $invoiceA->invoice_number)
            ->assertSee((string) $invoiceB->invoice_number)
            ->assertSee('1 movement');
    }

    public function test_shows_fully_collected_movements_by_default(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $fullyPaidInvoice = Invoice::factory()->create(['total' => 5000]);
        $partialInvoice = Invoice::factory()->create(['total' => 10000]);

        $this->createMovementWithInvoices('2026-01-26', 5000, [$fullyPaidInvoice]);
        $this->createMovementWithInvoices('2026-01-26', 8000, [$partialInvoice]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee((string) $partialInvoice->invoice_number)
            ->assertSee((string) $fullyPaidInvoice->invoice_number)
            ->assertSee('2 movements');
    }

    public function test_shows_correct_totals_based_on_movement_deposits(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $invoiceA = Invoice::factory()->create(['total' => 10000]);
        $invoiceB = Invoice::factory()->create(['total' => 5000]);
        $invoiceC = Invoice::factory()->create(['total' => 12000]);

        $this->createMovementWithInvoices('2026-01-26', 13000, [$invoiceA, $invoiceB]);
        $this->createMovementWithInvoices('2026-01-26', 10000, [$invoiceC]);

        // Total invoiced = 27,000; collected = 23,000; remaining = 4,000
        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee('27,000')
            ->assertSee('23,000')
            ->assertSee('4,000');
    }

    public function test_uses_movement_deposit_when_less_than_linked_invoice_total(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $invoices = Invoice::factory()->count(5)->create(['total' => 12000]);
        $this->createMovementWithInvoices('2026-01-26', 50040.61, $invoices->all());

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee('50,040.61')
            ->assertSee('60,000');
    }

    public function test_filters_by_movement_date_range(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $invoiceA = Invoice::factory()->create(['total' => 1000]);
        $invoiceB = Invoice::factory()->create(['total' => 2000]);
        $invoiceC = Invoice::factory()->create(['total' => 3000]);

        $this->createMovementWithInvoices('2026-01-15', 500, [$invoiceA]);
        $this->createMovementWithInvoices('2026-01-26', 1000, [$invoiceB]);
        $this->createMovementWithInvoices('2026-02-01', 1500, [$invoiceC]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->set('dateFrom', '2026-01-20')
            ->set('dateTo', '2026-01-31')
            ->assertDontSee('15/01/2026')
            ->assertSee('26/01/2026')
            ->assertDontSee('01/02/2026');
    }

    public function test_filters_by_search(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $invoiceA = Invoice::factory()->create([
            'invoice_number' => 'INV-12345',
            'total' => 1000,
        ]);
        $invoiceB = Invoice::factory()->create([
            'invoice_number' => 'INV-99999',
            'total' => 2000,
        ]);

        $this->createMovementWithInvoices('2026-01-26', 500, [$invoiceA]);
        $this->createMovementWithInvoices('2026-01-26', 1000, [$invoiceB]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->set('search', '12345')
            ->assertSee('INV-12345')
            ->assertDontSee('INV-99999');
    }

    public function test_filters_by_search_across_movement_fields(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $invoiceA = Invoice::factory()->create(['total' => 1000]);
        $invoiceB = Invoice::factory()->create(['total' => 2000]);

        $this->createMovementWithInvoices('2026-01-26', 500, [$invoiceA]);
        BankMovement::create([
            'bank_account_id' => BankAccount::create([
                'bank_name' => 'LA CAIXA',
                'account_number' => 'PK00TEST0000000002',
                'currency' => 'EUR',
                'initial_balance' => 0,
                'current_balance' => 0,
            ])->id,
            'date' => '2026-01-26',
            'type' => 'transfer',
            'concept' => 'CONF:ANTICIPO',
            'reference' => 'VEST-REF',
            'deposit' => 1000,
            'listado_extra' => [
                'linked_invoice_ids' => [$invoiceB->id],
            ],
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->set('search', 'CONF:ANTICIPO')
            ->assertDontSee((string) $invoiceA->invoice_number)
            ->assertSee((string) $invoiceB->invoice_number);
    }

    public function test_filters_by_client_or_company_name(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $invoiceA = Invoice::factory()->create([
            'total' => 1000,
        ]);
        $invoiceB = Invoice::factory()->create([
            'total' => 2000,
        ]);

        $this->createMovementWithInvoices('2026-01-26', 500, [$invoiceA]);
        $this->createMovementWithInvoices('2026-01-26', 1000, [$invoiceB]);

        $searchTerm = (string) $invoiceA->client->name;

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->set('search', $searchTerm)
            ->assertSee((string) $invoiceA->invoice_number)
            ->assertDontSee((string) $invoiceB->invoice_number);
    }

    public function test_shows_empty_message_when_no_payment_movements(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee('No payment movements found');
    }

    public function test_hides_movements_without_linked_invoices(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $account = BankAccount::create([
            'bank_name' => 'Test Bank',
            'account_number' => 'PK00TEST0000000001',
            'currency' => 'EUR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        BankMovement::create([
            'bank_account_id' => $account->id,
            'date' => '2026-01-26',
            'type' => 'transfer',
            'concept' => 'Unlinked deposit',
            'deposit' => 1000,
            'listado_extra' => null,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee('No payment movements found');
    }

    public function test_shows_fully_paid_movement_when_search_matches(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $fullyPaidInvoice = Invoice::factory()->create([
            'invoice_number' => 'INV-FULLY-PAID',
            'total' => 5000,
        ]);
        $this->createMovementWithInvoices('2026-01-26', 5000, [$fullyPaidInvoice]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->set('search', 'INV-FULLY-PAID')
            ->assertSee('INV-FULLY-PAID')
            ->assertSee('0.00');
    }
}
