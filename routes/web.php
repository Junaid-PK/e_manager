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
