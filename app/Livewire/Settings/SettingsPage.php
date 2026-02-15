<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class SettingsPage extends Component
{
    public string $activeSection = 'general';

    public string $locale = 'en';
    public string $timezone = 'Europe/Madrid';
    public string $dateFormat = 'd/m/Y';
    public string $currencySymbol = '€';

    public float $defaultIvaRate = 21;
    public float $defaultRetentionRate = 0;
    public int $defaultPaymentTerms = 30;

    public string $profileName = '';
    public string $profileEmail = '';
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $this->locale = app()->getLocale();
        $user = Auth::user();
        if ($user) {
            $this->profileName = $user->name;
            $this->profileEmail = $user->email;
        }
    }

    public function switchSection(string $section): void
    {
        $this->activeSection = $section;
    }

    public function saveGeneral(): void
    {
        session(['locale' => $this->locale]);
        app()->setLocale($this->locale);
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function saveDefaults(): void
    {
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function saveProfile(): void
    {
        $this->validate([
            'profileName' => 'required|string|max:255',
            'profileEmail' => 'required|email|max:255',
        ]);

        $user = Auth::user();
        $user->update([
            'name' => $this->profileName,
            'email' => $this->profileEmail,
        ]);

        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:8|confirmed',
        ], [], [
            'newPassword' => 'new password',
        ]);

        if (!Hash::check($this->currentPassword, Auth::user()->password)) {
            $this->addError('currentPassword', 'The current password is incorrect.');
            return;
        }

        Auth::user()->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';

        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function render()
    {
        return view('livewire.settings.settings-page')
            ->layout('layouts.app');
    }
}
