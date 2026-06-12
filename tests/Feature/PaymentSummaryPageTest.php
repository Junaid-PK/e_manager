<?php

namespace Tests\Feature;

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

    public function test_shows_all_paid_invoices_by_default_grouped_by_date(): void
    {
        $user = $this->createUserWithPermission();

        $partialInvoice = Invoice::factory()->partiallyPaid()->create([
            'paid_date' => '2026-01-26',
        ]);
        $fullyPaidInvoice = Invoice::factory()->fullyPaid()->create([
            'paid_date' => '2026-01-26',
        ]);

        // Not shown: unpaid
        Invoice::factory()->create([
            'paid_date' => null,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee('26/01/2026')
            ->assertSee((string) $partialInvoice->invoice_number)
            ->assertSee((string) $fullyPaidInvoice->invoice_number)
            ->assertSee('2 invoices');
    }

    public function test_partial_only_filter_hides_fully_paid_invoices(): void
    {
        $user = $this->createUserWithPermission();

        $partialInvoice = Invoice::factory()->partiallyPaid()->create([
            'paid_date' => '2026-01-26',
        ]);
        $fullyPaidInvoice = Invoice::factory()->fullyPaid()->create([
            'paid_date' => '2026-01-26',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->set('partialOnly', true)
            ->assertSee((string) $partialInvoice->invoice_number)
            ->assertDontSee((string) $fullyPaidInvoice->invoice_number)
            ->assertSee('1 invoice');
    }

    public function test_shows_correct_totals_for_all_paid_invoices(): void
    {
        $user = $this->createUserWithPermission();

        Invoice::factory()->create([
            'total' => 10000,
            'amount_paid' => 10000,
            'amount_remaining' => 0,
            'status' => 'paid',
            'paid_date' => '2026-01-26',
        ]);
        Invoice::factory()->create([
            'total' => 5000,
            'amount_paid' => 3000,
            'amount_remaining' => 2000,
            'status' => 'partial',
            'paid_date' => '2026-01-26',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee('15,000') // total sum
            ->assertSee('13,000') // amount_paid sum
            ->assertSee('2,000'); // remaining sum
    }

    public function test_filters_by_date_range(): void
    {
        $user = $this->createUserWithPermission();

        Invoice::factory()->partiallyPaid()->create([
            'paid_date' => '2026-01-15',
        ]);
        Invoice::factory()->fullyPaid()->create([
            'paid_date' => '2026-01-26',
        ]);
        Invoice::factory()->partiallyPaid()->create([
            'paid_date' => '2026-02-01',
        ]);

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

        $invoice = Invoice::factory()->fullyPaid()->create([
            'invoice_number' => 'INV-12345',
            'paid_date' => '2026-01-26',
        ]);
        Invoice::factory()->fullyPaid()->create([
            'invoice_number' => 'INV-99999',
            'paid_date' => '2026-01-26',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->set('search', '12345')
            ->assertSee('INV-12345')
            ->assertDontSee('INV-99999');
    }

    public function test_shows_empty_message_when_no_paid_invoices(): void
    {
        $user = $this->createUserWithPermission();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invoices\PaymentSummaryPage::class)
            ->assertSee('No paid invoices found');
    }
}
