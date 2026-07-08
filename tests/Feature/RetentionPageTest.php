<?php

namespace Tests\Feature;

use App\Livewire\Invoices\RetentionPage;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RetentionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createUserWithPermission(string $permission = 'invoices.retention'): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'retention-test-role']);
        $permissionModel = \App\Models\Permission::where('name', $permission)->firstOrFail();
        $accessAllPermission = \App\Models\Permission::where('name', 'invoices.access_all')->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$permissionModel->id, $accessAllPermission->id]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    public function test_page_requires_authentication(): void
    {
        $this->get(route('invoices.retention'))
            ->assertRedirect(route('login'));
    }

    public function test_page_requires_retention_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'retention-viewer']);
        $viewPermission = \App\Models\Permission::where('name', 'invoices.view')->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$viewPermission->id]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->actingAs($user)
            ->get(route('invoices.retention'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_page_shows_only_invoices_with_retention_amount(): void
    {
        $user = $this->createUserWithPermission();

        $visibleInvoice = Invoice::factory()->create([
            'invoice_number' => 'RET-001',
            'retention_rate' => 5,
            'retention_amount' => 250,
            'paid_date' => null,
        ]);

        $hiddenInvoice = Invoice::factory()->create([
            'invoice_number' => 'NO-RET-001',
            'retention_rate' => 0,
            'retention_amount' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(RetentionPage::class)
            ->assertSee('RET-001')
            ->assertSee((string) $visibleInvoice->client?->name)
            ->assertSee('250')
            ->assertDontSee('NO-RET-001');
    }

    public function test_mark_as_paid_updates_only_retention_payment_state(): void
    {
        $user = $this->createUserWithPermission();
        $editPermission = \App\Models\Permission::where('name', 'invoices.edit')->firstOrFail();
        $user->roles->firstOrFail()->permissions()->syncWithoutDetaching([$editPermission->id]);

        $invoice = Invoice::factory()->create([
            'invoice_number' => 'RET-002',
            'retention_rate' => 7,
            'retention_amount' => 175,
            'amount_paid' => 300,
            'amount_remaining' => 700,
            'retention_paid_date' => null,
        ]);

        Livewire::actingAs($user)
            ->test(RetentionPage::class)
            ->call('markRetentionPaid', $invoice->id)
            ->assertDispatched('notify');

        $invoice->refresh();

        $this->assertNotNull($invoice->retention_paid_date);
        $this->assertSame(300.0, (float) $invoice->amount_paid);
        $this->assertSame(700.0, (float) $invoice->amount_remaining);
    }

    public function test_already_paid_retention_does_not_show_mark_paid_button(): void
    {
        $user = $this->createUserWithPermission();

        Invoice::factory()->create([
            'invoice_number' => 'RET-003',
            'retention_rate' => 3,
            'retention_amount' => 90,
            'retention_paid_date' => now()->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(RetentionPage::class)
            ->assertDontSee(__('app.mark_as_paid'));
    }
}
