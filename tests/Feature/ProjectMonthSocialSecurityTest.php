<?php

namespace Tests\Feature;

use App\Livewire\ProjectMonths\ProjectMonthDetailPage;
use App\Livewire\WorkerProjectEntries\WorkerProjectEntryPage;
use App\Livewire\Workers\WorkerPage;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\Company;
use App\Models\MonthlyPeriod;
use App\Models\Project;
use App\Models\ProjectMonth;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectMonthSocialSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs($this->admin);
    }

    public function test_worker_roles_and_their_social_security_rates_can_be_configured(): void
    {
        Livewire::test(WorkerPage::class)
            ->set('peonSocialSecurityRate', '18.5')
            ->set('expertSocialSecurityRate', '32')
            ->call('saveSocialSecurityRate')
            ->set('formFullName', 'Configured Worker')
            ->set('formRole', 'expert')
            ->set('formRate', '20')
            ->call('saveInline')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('workers', [
            'full_name' => 'Configured Worker',
            'role' => 'expert',
            'rate' => 20,
        ]);
        $this->assertDatabaseHas('app_settings', ['key' => 'peon_social_security_rate', 'value' => '18.5']);
        $this->assertDatabaseHas('app_settings', ['key' => 'expert_social_security_rate', 'value' => '32']);

        $worker = Worker::where('full_name', 'Configured Worker')->firstOrFail();
        Livewire::test(WorkerPage::class)
            ->call('quickUpdateRole', $worker->id, 'peon');

        $this->assertDatabaseHas('workers', ['id' => $worker->id, 'role' => 'peon']);
    }

    public function test_hours_use_the_social_security_rate_for_the_workers_role(): void
    {
        AppSetting::setSocialSecurityRates(10, 30);
        $peon = Worker::create([
            'full_name' => 'Peon Worker',
            'role' => 'peon',
            'rate' => 20,
        ]);
        $expert = Worker::create([
            'full_name' => 'Expert Worker',
            'role' => 'expert',
            'rate' => 20,
        ]);
        $projectMonth = $this->projectMonth();

        Livewire::test(ProjectMonthDetailPage::class, ['projectMonth' => $projectMonth])
            ->set('formWorkerId', (string) $peon->id)
            ->set('formHours', '90')
            ->assertSet('formRate', '20.00')
            ->assertSet('formDays', '10')
            ->assertSet('formSocialSecurity', '180');

        Livewire::test(ProjectMonthDetailPage::class, ['projectMonth' => $projectMonth])
            ->set('formWorkerId', (string) $expert->id)
            ->set('formHours', '90')
            ->assertSet('formSocialSecurity', '540');
    }

    public function test_worker_entry_page_uses_the_same_configured_calculation(): void
    {
        AppSetting::setSocialSecurityRates(10, 30);
        $worker = Worker::create([
            'full_name' => 'Expert Worker',
            'role' => 'expert',
            'rate' => 20,
        ]);
        $this->projectMonth();

        Livewire::test(WorkerProjectEntryPage::class)
            ->set('formWorkerId', (string) $worker->id)
            ->set('formHours', '90')
            ->assertSet('formRate', '20.00')
            ->assertSet('formDays', '10')
            ->assertSet('formSocialSecurity', '540');
    }

    private function projectMonth(): ProjectMonth
    {
        $company = Company::create(['name' => 'Test Company']);
        $client = Client::create(['name' => 'Test Client']);
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Test Project',
        ]);
        $period = MonthlyPeriod::firstOrCreateForMonth(2026, 7);

        return ProjectMonth::create([
            'monthly_period_id' => $period->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
        ]);
    }
}
