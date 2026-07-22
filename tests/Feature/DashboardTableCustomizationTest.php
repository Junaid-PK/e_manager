<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardPage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTableCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_tables_expose_browser_persisted_column_and_row_preferences(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->roles()->attach($adminRole);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee(__('app.customize_table'))
            ->assertSee('dashboardTablePreferences:v1:', false)
            ->assertSee("dashboardTablePreferences('monthly-matrix'", false)
            ->assertSee("dashboardTablePreferences('types-by-month'", false)
            ->assertSee("dashboardTablePreferences('types-by-year'", false)
            ->assertSee('toggleColumn(column.key)', false)
            ->assertSee('toggleRow(row.key)', false)
            ->assertSee('data-dashboard-customizer-scroll', false);
    }

    public function test_dashboard_can_switch_between_month_year_and_custom_date_periods(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->roles()->attach($adminRole);

        Livewire::actingAs($user)
            ->test(DashboardPage::class)
            ->assertSeeHtml('wire:model.live="statsMonth"')
            ->assertSeeHtml('wire:model.live="statsYear"')
            ->set('statsMonth', '2025-04')
            ->assertSet('statsDateFrom', '2025-04-01')
            ->assertSet('statsDateTo', '2025-04-30')
            ->assertSet('statsYear', '')
            ->set('statsYear', '2024')
            ->assertSet('statsMonth', '')
            ->assertSet('statsDateFrom', '2024-01-01')
            ->assertSet('statsDateTo', '2024-12-31')
            ->set('statsDateFrom', '2024-03-01')
            ->assertSet('statsMonth', '')
            ->assertSet('statsYear', '');
    }
}
