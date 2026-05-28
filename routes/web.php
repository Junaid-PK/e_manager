<?php

use App\Http\Controllers\ExportDownloadController;
use App\Livewire\BankAccounts\BankAccountPage;
use App\Livewire\CompaniesClients\CompaniesClientsPage;
use App\Livewire\CreditLines\CreditLinePage;
use App\Livewire\Dashboard\DashboardPage;
use App\Livewire\Expenses\ExpenseListadoConfigPage;
use App\Livewire\Expenses\ExpensePage;
use App\Livewire\Invoices\InvoicePage;
use App\Livewire\MovementConfig\MovementConfigPage;
use App\Livewire\Movements\MovementPage;
use App\Livewire\Reminders\ReminderPage;
use App\Livewire\Reports\ReportsPage;
use App\Livewire\Settings\SettingsPage;
use App\Livewire\Users\UsersPage;
use App\Livewire\Roles\RolesPage;
use App\Livewire\Workers\WorkerPage;
use App\Livewire\MonthlyPeriods\MonthlyPeriodPage;
use App\Livewire\ProjectExpenses\ProjectExpensePage;
use App\Livewire\ProjectInvoices\ProjectInvoicePage;
use App\Livewire\ProjectMonths\ProjectMonthPage;
use App\Livewire\WorkerMonthlySummaries\WorkerMonthlySummaryPage;
use App\Livewire\WorkerPayments\WorkerPaymentPage;
use App\Livewire\WorkerProjectEntries\WorkerProjectEntryPage;
use App\Livewire\PeriodDashboard\PeriodDashboardPage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('download/export/{file}', ExportDownloadController::class)->middleware('signed')->name('export.download');
    Route::get('dashboard', DashboardPage::class)->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('invoices', InvoicePage::class)->middleware('permission:invoices.view')->name('invoices');
    Route::get('bank-accounts', BankAccountPage::class)->middleware('permission:bank_accounts.view')->name('bank-accounts');
    Route::get('movements', MovementPage::class)->middleware('permission:movements.view')->name('movements');
    Route::get('movement-config', MovementConfigPage::class)->middleware('permission:movements.view')->name('movement-config');
    Route::get('expenses', ExpensePage::class)->middleware('permission:expenses.view')->name('expenses');
    Route::get('expense-listado-config', ExpenseListadoConfigPage::class)->middleware('permission:expenses.view')->name('expense-listado-config');
    Route::get('companies-clients', CompaniesClientsPage::class)->middleware('permission:companies_clients.view')->name('companies-clients');
    Route::get('credit-lines', CreditLinePage::class)->middleware('permission:credit_lines.view')->name('credit-lines');
    Route::get('reminders', ReminderPage::class)->middleware('permission:reminders.view')->name('reminders');
    Route::get('reports', ReportsPage::class)->middleware('permission:reports.view')->name('reports');
    Route::get('users', UsersPage::class)->middleware('permission:users.view')->name('users');
    Route::get('roles', RolesPage::class)->middleware('permission:roles.view')->name('roles');
    Route::get('settings', SettingsPage::class)->middleware('permission:settings.view')->name('settings');
    Route::get('workers', WorkerPage::class)->middleware('permission:workers.view')->name('workers');
    Route::get('monthly-periods', MonthlyPeriodPage::class)->middleware('permission:monthly_periods.view')->name('monthly-periods');
    Route::get('project-months', ProjectMonthPage::class)->middleware('permission:project_months.view')->name('project-months');
    Route::get('project-invoices', ProjectInvoicePage::class)->middleware('permission:project_invoices.view')->name('project-invoices');
    Route::get('project-expenses', ProjectExpensePage::class)->middleware('permission:project_expenses.view')->name('project-expenses');
    Route::get('worker-monthly-summaries', WorkerMonthlySummaryPage::class)->middleware('permission:worker_monthly_summaries.view')->name('worker-monthly-summaries');
    Route::get('worker-payments', WorkerPaymentPage::class)->middleware('permission:worker_payments.view')->name('worker-payments');
    Route::get('worker-project-entries', WorkerProjectEntryPage::class)->middleware('permission:worker_project_entries.view')->name('worker-project-entries');
    Route::get('period-dashboard', PeriodDashboardPage::class)->middleware('permission:period_dashboard.view')->name('period-dashboard');
});

Route::get('lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'es'])) {
        Session::put('locale', $locale);
        App::setLocale($locale);
    }

    return redirect()->back();
})->name('lang.switch');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
