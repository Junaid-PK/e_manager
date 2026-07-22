<?php

namespace Tests\Feature;

use App\Livewire\CompaniesClients\ProjectTable;
use App\Livewire\ProjectMonths\ProjectMonthPage;
use App\Models\Client;
use App\Models\Company;
use App\Models\MonthlyPeriod;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectClientLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs($user);
    }

    public function test_project_can_be_linked_to_a_client_from_companies_and_clients(): void
    {
        $company = Company::create(['name' => 'Construction Company']);
        $client = Client::create(['name' => 'Linked Client']);

        Livewire::test(ProjectTable::class)
            ->set('formCompanyId', (string) $company->id)
            ->set('formClientId', (string) $client->id)
            ->set('formName', 'Linked Project')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'Linked Project',
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_project_month_projects_are_filtered_and_validated_by_client(): void
    {
        $company = Company::create(['name' => 'Construction Company']);
        $clientA = Client::create(['name' => 'Client A']);
        $clientB = Client::create(['name' => 'Client B']);
        $projectA = Project::create([
            'company_id' => $company->id,
            'client_id' => $clientA->id,
            'name' => 'Project A',
        ]);
        $projectB = Project::create([
            'company_id' => $company->id,
            'client_id' => $clientB->id,
            'name' => 'Project B',
        ]);
        $period = MonthlyPeriod::firstOrCreateForMonth(2026, 7);

        $component = Livewire::test(ProjectMonthPage::class)
            ->set('formClientId', (string) $clientA->id);

        $formProjectIds = $component->instance()
            ->render()
            ->getData()['formProjects']
            ->pluck('id')
            ->all();

        $this->assertSame([$projectA->id], $formProjectIds);

        $component
            ->set('formPeriodId', (string) $period->id)
            ->set('formProjectId', (string) $projectB->id)
            ->call('save')
            ->assertHasErrors(['formProjectId']);
    }
}
