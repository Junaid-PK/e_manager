<?php

namespace App\Livewire\Reminders;

use App\Models\PaymentReminder;
use Livewire\Component;

class ReminderBell extends Component
{
    public bool $showDropdown = false;

    public function toggleDropdown(): void
    {
        $this->showDropdown = !$this->showDropdown;
    }

    public function dismiss(int $id): void
    {
        PaymentReminder::findOrFail($id)->update(['is_dismissed' => true]);
    }

    public function render()
    {
        $activeCount = PaymentReminder::active()->count();
        $reminders = PaymentReminder::with('remindable')
            ->active()
            ->orderBy('reminder_date')
            ->limit(5)
            ->get();

        return view('livewire.reminders.reminder-bell', [
            'activeCount' => $activeCount,
            'reminders' => $reminders,
        ]);
    }
}
