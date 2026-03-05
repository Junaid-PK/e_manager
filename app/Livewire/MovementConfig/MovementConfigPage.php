<?php

namespace App\Livewire\MovementConfig;

use App\Models\MovementCategory;
use App\Models\MovementType;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class MovementConfigPage extends Component
{
    use WithPagination, WithFileUploads;

    public string $activeTab = 'types';

    public bool $showTypeModal = false;
    public bool $showCategoryModal = false;
    public bool $showDeleteModal = false;
    public bool $showImportModal = false;
    public ?int $editingId = null;
    public string $deleteTarget = '';

    public string $typeName = '';
    public string $typeColor = '#10b981';
    public int $typeSortOrder = 0;

    public string $categoryName = '';
    public string $categoryParentId = '';
    public int $categorySortOrder = 0;

    public $importFile;
    public string $importTarget = 'types';

    public string $searchTypes = '';
    public string $searchCategories = '';

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function createType(): void
    {
        $this->resetTypeForm();
        $this->editingId = null;
        $this->showTypeModal = true;
    }

    public function editType(int $id): void
    {
        $type = MovementType::findOrFail($id);
        $this->editingId = $id;
        $this->typeName = $type->name;
        $this->typeColor = $type->color ?? '#10b981';
        $this->typeSortOrder = $type->sort_order;
        $this->showTypeModal = true;
    }

    public function saveType(): void
    {
        $this->validate([
            'typeName' => 'required|string|max:255',
            'typeColor' => 'nullable|string|max:20',
            'typeSortOrder' => 'integer|min:0',
        ]);

        $data = [
            'name' => $this->typeName,
            'color' => $this->typeColor ?: null,
            'sort_order' => $this->typeSortOrder,
        ];

        if ($this->editingId) {
            MovementType::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            MovementType::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showTypeModal = false;
        $this->resetTypeForm();
    }

    public function createCategory(): void
    {
        $this->resetCategoryForm();
        $this->editingId = null;
        $this->showCategoryModal = true;
    }

    public function editCategory(int $id): void
    {
        $cat = MovementCategory::findOrFail($id);
        $this->editingId = $id;
        $this->categoryName = $cat->name;
        $this->categoryParentId = (string) ($cat->parent_id ?? '');
        $this->categorySortOrder = $cat->sort_order;
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'categoryName' => 'required|string|max:255',
            'categoryParentId' => 'nullable|exists:movement_categories,id',
            'categorySortOrder' => 'integer|min:0',
        ]);

        $data = [
            'name' => $this->categoryName,
            'parent_id' => $this->categoryParentId ?: null,
            'sort_order' => $this->categorySortOrder,
        ];

        if ($this->editingId) {
            MovementCategory::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            MovementCategory::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    public function confirmDelete(string $target, int $id): void
    {
        $this->deleteTarget = $target;
        $this->editingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->editingId) {
            if ($this->deleteTarget === 'type') {
                MovementType::findOrFail($this->editingId)->delete();
            } else {
                MovementCategory::findOrFail($this->editingId)->delete();
            }
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function openImport(string $target): void
    {
        $this->importTarget = $target;
        $this->importFile = null;
        $this->showImportModal = true;
    }

    public function processImport(): void
    {
        $this->validate(['importFile' => 'required|file|mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream']);

        $data = Excel::toArray(null, $this->importFile->getRealPath());
        if (empty($data) || empty($data[0])) {
            $this->dispatch('notify', type: 'error', message: __('app.no_records_imported'));
            $this->showImportModal = false;
            return;
        }

        $rows = $data[0];
        $hasHeader = !empty($rows[0]) && is_string($rows[0][0]) && !is_numeric($rows[0][0]);
        $startIndex = $hasHeader ? 1 : 0;
        $imported = 0;

        for ($i = $startIndex; $i < count($rows); $i++) {
            $name = trim((string) ($rows[$i][0] ?? ''));
            if (empty($name)) continue;

            if ($this->importTarget === 'types') {
                $extra = [
                    'color' => trim((string) ($rows[$i][1] ?? '')) ?: null,
                    'sort_order' => (int) ($rows[$i][2] ?? 0),
                ];
                MovementType::firstOrCreate(['name' => $name], $extra);
            } else {
                $parentName = trim((string) ($rows[$i][1] ?? ''));
                $parentId = null;
                if ($parentName) {
                    $parent = MovementCategory::firstOrCreate(['name' => $parentName]);
                    $parentId = $parent->id;
                }
                MovementCategory::firstOrCreate(['name' => $name], [
                    'parent_id' => $parentId,
                    'sort_order' => (int) ($rows[$i][2] ?? 0),
                ]);
            }
            $imported++;
        }

        $this->showImportModal = false;
        $this->dispatch('notify', type: 'success', message: $imported . ' ' . __('app.records_imported'));
    }

    private function resetTypeForm(): void
    {
        $this->typeName = '';
        $this->typeColor = '#10b981';
        $this->typeSortOrder = 0;
        $this->resetValidation();
    }

    private function resetCategoryForm(): void
    {
        $this->categoryName = '';
        $this->categoryParentId = '';
        $this->categorySortOrder = 0;
        $this->resetValidation();
    }

    public function render()
    {
        $typesQuery = MovementType::orderBy('sort_order')->orderBy('name');
        if ($this->searchTypes) {
            $typesQuery->where('name', 'like', "%{$this->searchTypes}%");
        }

        $categoriesQuery = MovementCategory::with('parent')->orderBy('sort_order')->orderBy('name');
        if ($this->searchCategories) {
            $categoriesQuery->where('name', 'like', "%{$this->searchCategories}%");
        }

        return view('livewire.movement-config.movement-config-page', [
            'types' => $typesQuery->get(),
            'categories' => $categoriesQuery->get(),
            'parentCategories' => MovementCategory::whereNull('parent_id')->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
