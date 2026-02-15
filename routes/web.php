<?php

use App\Livewire\BankAccounts\BankAccountPage;
use App\Livewire\CompaniesClients\CompaniesClientsPage;
use App\Livewire\Dashboard\DashboardPage;
use App\Livewire\Expenses\ExpensePage;
use App\Livewire\Invoices\InvoicePage;
use App\Livewire\Movements\MovementPage;
use App\Livewire\Reminders\ReminderPage;
use App\Livewire\Reports\ReportsPage;
use App\Livewire\Settings\SettingsPage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardPage::class)->name('dashboard');
    Route::get('invoices', InvoicePage::class)->name('invoices');
    Route::get('bank-accounts', BankAccountPage::class)->name('bank-accounts');
    Route::get('movements', MovementPage::class)->name('movements');
    Route::get('expenses', ExpensePage::class)->name('expenses');
    Route::get('companies-clients', CompaniesClientsPage::class)->name('companies-clients');
    Route::get('reminders', ReminderPage::class)->name('reminders');
    Route::get('reports', ReportsPage::class)->name('reports');
    Route::get('settings', SettingsPage::class)->name('settings');
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
