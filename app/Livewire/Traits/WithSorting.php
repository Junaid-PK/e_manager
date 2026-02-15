<?php

namespace App\Livewire\Traits;

trait WithSorting
{
    public string $sortField = '';
    public string $sortDirection = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    protected function applySorting($query)
    {
        if ($this->sortField) {
            return $query->orderBy($this->sortField, $this->sortDirection);
        }
        return $query->latest();
    }
}
