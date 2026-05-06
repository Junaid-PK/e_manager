<?php

namespace Tests\Feature;

use App\Livewire\Expenses\ExpenseListadoConfigPage;
use App\Models\ExpenseCif;
use App\Models\ExpenseProvider;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseListadoConfigImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole->id);
    }

    public function test_provider_import_creates_and_links_providers_and_cifs_from_csv(): void
    {
        $this->actingAs($this->admin);

        ExpenseProvider::create([
            'name' => 'BANCO SANTANDER',
            'sort_order' => 1,
        ]);

        $file = UploadedFile::fake()->createWithContent('providers.csv', implode("\n", [
            'CIF,NOMBRE /RAZÓN SOCIAL',
            'A39000013,BANCO SANTANDER',
            'B84406289,BRICOLAJE BRICOMAN SL',
            'B84406289,BRICOLAJE BRICOMAN SL',
        ]));

        Livewire::test(ExpenseListadoConfigPage::class)
            ->call('openImport', 'providers')
            ->set('importFile', $file)
            ->call('processImport')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expense_cifs', ['code' => 'A39000013']);
        $this->assertDatabaseHas('expense_cifs', ['code' => 'B84406289']);
        $this->assertDatabaseHas('expense_providers', ['name' => 'BRICOLAJE BRICOMAN SL']);

        $santander = ExpenseProvider::query()->where('name', 'BANCO SANTANDER')->firstOrFail();
        $bricoman = ExpenseProvider::query()->where('name', 'BRICOLAJE BRICOMAN SL')->firstOrFail();

        $this->assertSame('A39000013', $santander->cif?->code);
        $this->assertSame('B84406289', $bricoman->cif?->code);
        $this->assertSame(2, ExpenseCif::count());
        $this->assertSame(2, ExpenseProvider::count());
    }

    public function test_cif_import_accepts_first_column_when_file_has_no_header(): void
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->createWithContent('cifs.csv', implode("\n", [
            'Y4478591E',
            'A80298839',
            'Y4478591E',
        ]));

        Livewire::test(ExpenseListadoConfigPage::class)
            ->call('openImport', 'cifs')
            ->set('importFile', $file)
            ->call('processImport')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expense_cifs', ['code' => 'Y4478591E']);
        $this->assertDatabaseHas('expense_cifs', ['code' => 'A80298839']);
        $this->assertSame(2, ExpenseCif::count());
    }
}
