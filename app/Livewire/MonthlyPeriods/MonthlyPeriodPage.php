<?php

namespace App\Livewire\MonthlyPeriods;

use App\Exports\MonthlyPeriodExport;
use App\Livewire\Traits\WithBulkActions;
use App\Livewire\Traits\WithFiltering;
use App\Livewire\Traits\WithSorting;
use App\Models\MonthlyPeriod;
use App\Services\MonthlyPeriodImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyPeriodPage extends Component
{
    use WithBulkActions, WithFiltering, WithPagination, WithSorting, WithFileUploads;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showImportModal = false;
    public bool $showGenerateModal = false;
    public ?int $editingId = null;

    public string $viewMode = 'grid';
    public int $currentYear;

    public string $formPeriodCode = '';
    public int $formYear = 0;
    public int $formMonth = 1;
    public string $formLabel = '';
    public string $formStartDate = '';
    public string $formEndDate = '';

    public int $generateYear = 0;

    public $importFile = null;
    public array $importPreview = [];
    public array $importColumnMap = [];
    public int $importStep = 1;

    public function mount(): void
    {
        $this->currentYear = (int) now()->format('Y');
        $this->generateYear = $this->currentYear;
        $this->formYear = $this->currentYear;
    }

    protected function rules(): array
    {
        return [
            'formPeriodCode' => 'required|string|max:20|unique:monthly_periods,period_code,' . $this->editingId,
            'formYear' => 'required|integer|min:2000|max:2100',
            'formMonth' => 'required|integer|min:1|max:12',
            'formLabel' => 'required|string|max:100',
            'formStartDate' => 'nullable|date',
            'formEndDate' => 'nullable|date|after_or_equal:formStartDate',
        ];
    }

    public function previousYear(): void
    {
        $this->currentYear--;
        $this->resetPage();
        $this->deselectAll();
    }

    public function nextYear(): void
    {
        $this->currentYear++;
        $this->resetPage();
        $this->deselectAll();
    }

    public function goToCurrentYear(): void
    {
        $this->currentYear = (int) now()->format('Y');
        $this->resetPage();
        $this->deselectAll();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['grid', 'table']) ? $mode : 'grid';
    }

    public function openGenerateModal(): void
    {
        Gate::authorize('monthly_periods.create');
        $this->generateYear = $this->currentYear;
        $this->showGenerateModal = true;
    }

    public function generateYearPeriods(): void
    {
        Gate::authorize('monthly_periods.create');

        $year = $this->generateYear;
        $created = 0;

        for ($month = 1; $month <= 12; $month++) {
            $periodCode = sprintf('%04d-%02d', $year, $month);
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            $label = date('F Y', strtotime($startDate));

            MonthlyPeriod::firstOrCreate(
                ['period_code' => $periodCode],
                [
                    'year' => $year,
                    'month' => $month,
                    'label' => $label,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            );
            $created++;
        }

        $this->showGenerateModal = false;
        $this->dispatch('notify', type: 'success', message: $created . ' ' . __('app.periods_generated'));
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->formYear = $this->currentYear;
        $this->autoFillFromMonth();
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $period = MonthlyPeriod::findOrFail($id);
        $this->editingId = $id;
        $this->formPeriodCode = $period->period_code;
        $this->formYear = $period->year;
        $this->formMonth = $period->month;
        $this->formLabel = $period->label;
        $this->formStartDate = $period->start_date?->format('Y-m-d') ?? '';
        $this->formEndDate = $period->end_date?->format('Y-m-d') ?? '';
        $this->showFormModal = true;
    }

    public function updatedFormYear(): void
    {
        $this->autoFillFromMonth();
    }

    public function updatedFormMonth(): void
    {
        $this->autoFillFromMonth();
    }

    private function autoFillFromMonth(): void
    {
        if ($this->formYear > 0 && $this->formMonth >= 1 && $this->formMonth <= 12) {
            $this->formPeriodCode = sprintf('%04d-%02d', $this->formYear, $this->formMonth);
            $startDate = sprintf('%04d-%02d-01', $this->formYear, $this->formMonth);
            $this->formLabel = date('F Y', strtotime($startDate));
            $this->formStartDate = $startDate;
            $this->formEndDate = date('Y-m-t', strtotime($startDate));
        }
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize('monthly_periods.edit');
        } else {
            Gate::authorize('monthly_periods.create');
        }

        $this->validate();

        $data = [
            'period_code' => $this->formPeriodCode,
            'year' => $this->formYear,
            'month' => $this->formMonth,
            'label' => $this->formLabel,
            'start_date' => $this->formStartDate ?: null,
            'end_date' => $this->formEndDate ?: null,
        ];

        if ($this->editingId) {
            MonthlyPeriod::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            MonthlyPeriod::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->editingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Gate::authorize('monthly_periods.delete');
        if ($this->editingId) {
            MonthlyPeriod::findOrFail($this->editingId)->delete();
            $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        }
        $this->showDeleteModal = false;
        $this->editingId = null;
    }

    public function deleteSelected(): void
    {
        Gate::authorize('monthly_periods.delete');
        MonthlyPeriod::whereIn('id', $this->selected)->delete();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
        $this->deselectAll();
    }

    public function exportToExcel()
    {
        Gate::authorize('monthly_periods.export');
        $periods = $this->buildQuery()->get();
        $filename = 'monthly-periods-'.date('Y-m-d-His').'-'.uniqid().'.xlsx';
        Storage::disk('local')->makeDirectory('exports');
        Excel::store(new MonthlyPeriodExport($periods), 'exports/'.$filename, 'local');

        return redirect(URL::temporarySignedRoute('export.download', now()->addMinutes(5), ['file' => $filename]));
    }

    public function openImportModal(): void
    {
        Gate::authorize('monthly_periods.create');
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;
        $this->showImportModal = true;
    }

    public function updatedImportFile(): void
    {
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new MonthlyPeriodImportService();
        $result = $service->parseFile($path);

        $this->importPreview = $result;

        $headers = $result['headers'] ?? [];
        $map = [];
        foreach ($headers as $idx => $header) {
            $lower = mb_strtolower(trim($header));
            if (str_contains($lower, 'code') || str_contains($lower, 'period')) {
                $map['period_code'] = $idx;
            } elseif (str_contains($lower, 'year') || str_contains($lower, 'año')) {
                $map['year'] = $idx;
            } elseif (str_contains($lower, 'month') || str_contains($lower, 'mes')) {
                $map['month'] = $idx;
            } elseif (str_contains($lower, 'label') || str_contains($lower, 'nombre')) {
                $map['label'] = $idx;
            } elseif (str_contains($lower, 'start') || str_contains($lower, 'inicio')) {
                $map['start_date'] = $idx;
            } elseif (str_contains($lower, 'end') || str_contains($lower, 'fin')) {
                $map['end_date'] = $idx;
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importPeriods(): void
    {
        Gate::authorize('monthly_periods.create');
        if (!$this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new MonthlyPeriodImportService();
        $result = $service->importMappedData($path, $this->importColumnMap);

        $this->showImportModal = false;
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;

        if ($result['imported'] > 0) {
            $this->dispatch('notify', type: 'success', message: $result['imported'].' '.__('app.rows_imported'));
        }

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->dispatch('notify', type: 'error', message: $error);
            }
        }
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->formPeriodCode = '';
        $this->formYear = $this->currentYear;
        $this->formMonth = 1;
        $this->formLabel = '';
        $this->formStartDate = '';
        $this->formEndDate = '';
        $this->resetValidation();
    }

    public function render()
    {
        $query = $this->buildQuery();
        $periods = $query->paginate($this->perPage);
        $allPeriods = $query->get();

        $stats = [
            'total' => $allPeriods->count(),
            'with_data' => $allPeriods->filter(fn ($p) => $p->has_data)->count(),
            'without_data' => $allPeriods->filter(fn ($p) => !$p->has_data)->count(),
        ];

        return view('livewire.monthly-periods.monthly-period-page', [
            'periods' => $periods,
            'allPeriods' => $allPeriods,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    protected function buildQuery()
    {
        $query = MonthlyPeriod::query()->where('year', $this->currentYear);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('period_code', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        return $this->applySorting($query);
    }

    protected function getPageItemIds(): array
    {
        return $this->buildQuery()->paginate($this->perPage)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    protected function getAllItemIds(): array
    {
        return $this->buildQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }
}
