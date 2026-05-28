<?php

namespace App\Livewire\ProjectMonths;

use App\Models\ProjectMonth;
use App\Models\Worker;
use App\Models\WorkerProjectEntry;
use App\Services\Mon83ImportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProjectMonthDetailPage extends Component
{
    use WithFileUploads;

    public ProjectMonth $projectMonth;

    public bool $showAddWorkerModal = false;
    public bool $showImportModal = false;

    public string $formWorkerId = '';
    public string $formSocialSecurity = '0';
    public string $formHours = '0';
    public string $formDays = '0';
    public string $formRate = '0';
    public string $formSpecialNote = '';

    public $importFile = null;
    public array $importPreview = [];
    public array $importColumnMap = [];
    public int $importStep = 1;

    protected function rules(): array
    {
        return [
            'formWorkerId' => 'required|exists:workers,id',
            'formSocialSecurity' => 'nullable|numeric|min:0',
            'formHours' => 'nullable|numeric|min:0',
            'formDays' => 'nullable|numeric|min:0',
            'formRate' => 'nullable|numeric|min:0',
            'formSpecialNote' => 'nullable|string|max:500',
        ];
    }

    public function mount(ProjectMonth $projectMonth): void
    {
        $this->projectMonth = $projectMonth->load(['monthlyPeriod', 'client', 'project']);
    }

    public function addWorker(): void
    {
        Gate::authorize('worker_project_entries.create');
        $this->validate();

        WorkerProjectEntry::create([
            'project_month_id' => $this->projectMonth->id,
            'worker_id' => (int) $this->formWorkerId,
            'social_security' => (float) ($this->formSocialSecurity ?: 0),
            'hours' => (float) ($this->formHours ?: 0),
            'days' => (float) ($this->formDays ?: 0),
            'rate' => (float) ($this->formRate ?: 0),
            'special_note' => $this->formSpecialNote ?: null,
        ]);

        $this->showAddWorkerModal = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: __('app.worker_added'));
    }

    public function quickUpdateField(int $entryId, string $field, string $value): void
    {
        Gate::authorize('worker_project_entries.edit');

        $entry = WorkerProjectEntry::where('project_month_id', $this->projectMonth->id)
            ->findOrFail($entryId);

        $numericValue = (float) str_replace(',', '.', str_replace('.', '', $value));

        $allowedFields = ['social_security', 'hours', 'days', 'rate'];
        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        $entry->{$field} = $numericValue;
        $entry->save();
    }

    public function deleteEntry(int $entryId): void
    {
        Gate::authorize('worker_project_entries.delete');

        WorkerProjectEntry::where('project_month_id', $this->projectMonth->id)
            ->findOrFail($entryId)
            ->delete();

        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function copyFromPreviousPeriod(): void
    {
        Gate::authorize('worker_project_entries.create');

        $previousProjectMonth = ProjectMonth::where('client_id', $this->projectMonth->client_id)
            ->where('project_id', $this->projectMonth->project_id)
            ->where('id', '<', $this->projectMonth->id)
            ->orderByDesc('id')
            ->first();

        if (! $previousProjectMonth) {
            $this->dispatch('notify', type: 'error', message: __('app.no_previous_period_found'));
            return;
        }

        $previousEntries = WorkerProjectEntry::where('project_month_id', $previousProjectMonth->id)->get();
        $copied = 0;

        foreach ($previousEntries as $entry) {
            WorkerProjectEntry::create([
                'project_month_id' => $this->projectMonth->id,
                'worker_id' => $entry->worker_id,
                'social_security' => 0,
                'hours' => 0,
                'days' => 0,
                'rate' => $entry->rate,
                'special_note' => null,
            ]);
            $copied++;
        }

        $this->dispatch('notify', type: 'success', message: $copied . ' ' . __('app.workers_copied'));
    }

    public function openImportModal(): void
    {
        Gate::authorize('worker_project_entries.create');
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;
        $this->showImportModal = true;
    }

    public function updatedImportFile(): void
    {
        if (! $this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new Mon83ImportService();
        $result = $service->parseFile($path);

        $this->importPreview = $result;

        $headers = $result['headers'] ?? [];
        $map = [];
        foreach ($headers as $idx => $header) {
            $lower = mb_strtolower(trim($header));
            if (str_contains($lower, 'nie') || str_contains($lower, 'dni') || str_contains($lower, 'nif')) {
                $map['nie'] = $idx;
            } elseif (str_contains($lower, 'name') || str_contains($lower, 'nombre') || str_contains($lower, 'trabajador')) {
                $map['name'] = $idx;
            } elseif (str_contains($lower, 'ss') || str_contains($lower, 'social') || str_contains($lower, 'seguridad')) {
                $map['social_security'] = $idx;
            } elseif (str_contains($lower, 'hrs') || str_contains($lower, 'hours') || str_contains($lower, 'horas')) {
                $map['hours'] = $idx;
            } elseif (str_contains($lower, 'dias') || str_contains($lower, 'days')) {
                $map['days'] = $idx;
            } elseif (str_contains($lower, 'precio') || str_contains($lower, 'rate') || str_contains($lower, 'price')) {
                $map['rate'] = $idx;
            } elseif (str_contains($lower, 'pagado') || str_contains($lower, 'paid')) {
                $map['paid'] = $idx;
            }
        }
        $this->importColumnMap = $map;
        $this->importStep = 2;
    }

    public function importRows(): void
    {
        Gate::authorize('worker_project_entries.create');
        if (! $this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $service = new Mon83ImportService();
        $result = $service->importMappedData($path, $this->importColumnMap, $this->projectMonth->id);

        $this->showImportModal = false;
        $this->importFile = null;
        $this->importPreview = [];
        $this->importColumnMap = [];
        $this->importStep = 1;

        if ($result['imported'] > 0) {
            $this->dispatch('notify', type: 'success', message: $result['imported'].' '.__('app.rows_imported'));
        }

        if (! empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->dispatch('notify', type: 'error', message: $error);
            }
        }
    }

    public function resetForm(): void
    {
        $this->formWorkerId = '';
        $this->formSocialSecurity = '0';
        $this->formHours = '0';
        $this->formDays = '0';
        $this->formRate = '0';
        $this->formSpecialNote = '';
        $this->resetValidation();
    }

    public function render()
    {
        $entries = WorkerProjectEntry::where('project_month_id', $this->projectMonth->id)
            ->with('worker')
            ->orderBy('id')
            ->get();

        $totals = [
            'social_security' => $entries->sum('social_security'),
            'hours' => $entries->sum('hours'),
            'days' => $entries->sum('days'),
            'total_amount' => $entries->sum('total_amount'),
            'paid_amount' => $entries->sum(fn ($e) => $e->paid_amount),
        ];

        return view('livewire.project-months.project-month-detail-page', [
            'entries' => $entries,
            'totals' => $totals,
            'workers' => Worker::orderBy('full_name')->get(),
        ])->layout('layouts.app');
    }
}
