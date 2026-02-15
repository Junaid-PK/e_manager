<?php

namespace App\Livewire\Traits;

use Livewire\Attributes\Url;

trait WithFiltering
{
    #[Url(as: 'q')]
    public string $search = '';

    public int $perPage = 25;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }
}
