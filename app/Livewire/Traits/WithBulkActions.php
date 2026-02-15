<?php

namespace App\Livewire\Traits;

trait WithBulkActions
{
    public array $selected = [];
    public bool $selectAll = false;
    public bool $selectPage = false;

    public function updatedSelectPage(bool $value): void
    {
        $this->selected = $value ? $this->getPageItemIds() : [];
    }

    public function updatedSelected(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
    }

    public function selectAllItems(): void
    {
        $this->selectAll = true;
        $this->selected = $this->getAllItemIds();
    }

    public function deselectAll(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
        $this->selected = [];
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    abstract protected function getPageItemIds(): array;
    abstract protected function getAllItemIds(): array;
}
