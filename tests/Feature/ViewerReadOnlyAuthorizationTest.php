<?php

namespace Tests\Feature;

use App\Livewire\Invoices\InvoicePage;
use App\Livewire\Movements\MovementImportWizard;
use App\Livewire\Movements\MovementPage;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewerReadOnlyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    private Invoice $invoice;

    private BankMovement $movement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->viewer = User::factory()->create();
        $this->viewer->roles()->attach(Role::where('name', 'viewer')->firstOrFail());
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->viewer->id,
            'invoice_number' => 'VIEW-ONLY-INVOICE',
            'status' => 'pending',
            'payment_type' => 'transfer',
        ]);
        $account = BankAccount::create([
            'user_id' => $this->viewer->id,
            'bank_name' => 'Viewer Bank',
            'account_number' => '1234567890',
            'holder_name' => $this->viewer->name,
            'currency' => 'EUR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);
        $this->movement = BankMovement::create([
            'user_id' => $this->viewer->id,
            'bank_account_id' => $account->id,
            'date' => '2026-07-22',
            'type' => 'transfer',
            'concept' => 'VIEW-ONLY-MOVEMENT',
            'deposit' => 100,
        ]);

        $this->actingAs($this->viewer);
    }

    public function test_invoice_page_renders_static_fields_for_view_only_users(): void
    {
        Livewire::test(InvoicePage::class)
            ->assertSee('VIEW-ONLY-INVOICE')
            ->assertDontSeeHtml('submit-method="quickStatusUpdate"')
            ->assertDontSeeHtml('submit-method="quickUpdatePaymentType"')
            ->assertDontSeeHtml("\$wire.call('quickUpdatePaidDate'")
            ->assertDontSeeHtml('wire:click="edit('.$this->invoice->id.')"')
            ->assertDontSeeHtml('wire:model.live="selected"');
    }

    public function test_viewer_cannot_call_invoice_mutation_actions_directly(): void
    {
        $calls = [
            ['create', []],
            ['edit', [$this->invoice->id]],
            ['duplicate', [$this->invoice->id]],
            ['quickStatusUpdate', [$this->invoice->id, 'paid']],
            ['quickUpdatePaymentType', [$this->invoice->id, 'cash']],
            ['quickUpdateBankName', [$this->invoice->id, 'Other Bank']],
            ['quickUpdatePaidDate', [$this->invoice->id, '2026-07-23']],
            ['quickUpdateAmountPaid', [$this->invoice->id, '50']],
            ['quickUpdateProjectText', [$this->invoice->id, 'Unauthorized Project']],
            ['confirmDelete', [$this->invoice->id]],
            ['openQuickClientForm', []],
            ['openQuickCompanyForm', []],
            ['openReminderModal', [$this->invoice->id]],
        ];

        foreach ($calls as [$method, $parameters]) {
            Livewire::test(InvoicePage::class)
                ->call($method, ...$parameters)
                ->assertForbidden();
        }

        $this->invoice->refresh();
        $this->assertSame('pending', $this->invoice->status);
        $this->assertSame('transfer', $this->invoice->payment_type);
        $this->assertDatabaseMissing('projects', ['name' => 'Unauthorized Project']);
    }

    public function test_movement_page_hides_editing_controls_for_view_only_users(): void
    {
        Livewire::test(MovementPage::class)
            ->assertSee('VIEW-ONLY-MOVEMENT')
            ->assertDontSeeHtml("openInlineDropdown('type', {$this->movement->id}")
            ->assertDontSeeHtml('wire:click="edit('.$this->movement->id.')"')
            ->assertDontSeeHtml('wire:model.live="selected"');
    }

    public function test_viewer_cannot_call_movement_mutation_actions_directly(): void
    {
        $calls = [
            ['create', []],
            ['edit', [$this->movement->id]],
            ['quickUpdateType', [$this->movement->id, 'other']],
            ['quickUpdateCategory', [$this->movement->id, 'Unauthorized Category']],
            ['editInlineType', [$this->movement->id]],
            ['editInlineCategory', [$this->movement->id]],
            ['openCategoryModal', []],
            ['confirmDelete', [$this->movement->id]],
        ];

        foreach ($calls as [$method, $parameters]) {
            Livewire::test(MovementPage::class)
                ->call($method, ...$parameters)
                ->assertForbidden();
        }

        Livewire::test(MovementImportWizard::class)
            ->call('open')
            ->assertForbidden();

        $this->movement->refresh();
        $this->assertSame('transfer', $this->movement->type);
        $this->assertNull($this->movement->category);
        $this->assertDatabaseMissing('movement_categories', ['name' => 'Unauthorized Category']);
    }
}
