<?php

namespace App\Livewire\CompaniesClients;

use Livewire\Component;

class CompaniesClientsPage extends Component
{
    public string $activeTab = 'companies';

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.companies-clients.companies-clients-page')
            ->layout('layouts.app');
    }
}
