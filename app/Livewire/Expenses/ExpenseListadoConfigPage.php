<?php

namespace App\Livewire\Expenses;

use App\Models\ExpenseCif;
use App\Models\ExpenseProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseListadoConfigPage extends Component
{
    use WithFileUploads;

    public string $activeTab = 'providers';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public bool $showImportModal = false;

    public ?int $editingId = null;

    public string $deleteTarget = '';

    public string $providerName = '';

    public int $providerSortOrder = 0;

    /** Existing CIF id (expense_cifs.id) or empty. */
    public string $providerExpenseCifId = '';

    /** Optional: create this CIF and link when saving the provider. */
    public string $providerNewCifCode = '';

    public string $cifCode = '';

    public int $cifSortOrder = 0;

    public string $searchProviders = '';

    public string $searchCifs = '';

    public $importFile;

    public string $importTarget = 'providers';

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function createProvider(): void
    {
        Gate::authorize('expenses.edit');
        $this->resetProviderForm();
        $this->editingId = null;
        $this->providerSortOrder = (int) ExpenseProvider::max('sort_order') + 1;
        $this->showModal = true;
    }

    public function editProvider(int $id): void
    {
        Gate::authorize('expenses.edit');
        $p = ExpenseProvider::findOrFail($id);
        $this->editingId = $id;
        $this->providerName = $p->name;
        $this->providerSortOrder = $p->sort_order;
        $this->providerExpenseCifId = $p->expense_cif_id ? (string) $p->expense_cif_id : '';
        $this->providerNewCifCode = '';
        $this->showModal = true;
    }

    public function saveProvider(): void
    {
        Gate::authorize('expenses.edit');
        $newCifNormalized = mb_strtoupper(trim($this->providerNewCifCode));
        $this->providerNewCifCode = $newCifNormalized;
        $this->validate([
            'providerName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_providers', 'name')->ignore($this->editingId),
            ],
            'providerSortOrder' => 'integer|min:0',
            'providerExpenseCifId' => 'nullable|string',
            'providerNewCifCode' => 'nullable|string|max:32',
        ]);

        $effectiveCifId = null;
        if ($newCifNormalized !== '') {
            $maxC = (int) ExpenseCif::max('sort_order');
            $cifModel = ExpenseCif::firstOrCreate(
                ['code' => $newCifNormalized],
                ['sort_order' => $maxC + 1]
            );
            $effectiveCifId = $cifModel->id;
        } elseif ($this->providerExpenseCifId !== '') {
            $effectiveCifId = (int) $this->providerExpenseCifId;
            if (! ExpenseCif::whereKey($effectiveCifId)->exists()) {
                $effectiveCifId = null;
            }
        }

        $data = [
            'name' => trim($this->providerName),
            'sort_order' => $this->providerSortOrder,
            'expense_cif_id' => $effectiveCifId,
        ];

        if ($this->editingId) {
            ExpenseProvider::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            ExpenseProvider::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showModal = false;
        $this->resetProviderForm();
    }

    public function createCif(): void
    {
        Gate::authorize('expenses.edit');
        $this->resetCifForm();
        $this->editingId = null;
        $this->cifSortOrder = (int) ExpenseCif::max('sort_order') + 1;
        $this->showModal = true;
    }

    public function editCif(int $id): void
    {
        Gate::authorize('expenses.edit');
        $c = ExpenseCif::findOrFail($id);
        $this->editingId = $id;
        $this->cifCode = $c->code;
        $this->cifSortOrder = $c->sort_order;
        $this->showModal = true;
    }

    public function saveCif(): void
    {
        Gate::authorize('expenses.edit');
        $normalized = mb_strtoupper(trim($this->cifCode));
        $this->cifCode = $normalized;
        $this->validate([
            'cifCode' => [
                'required',
                'string',
                'max:32',
                Rule::unique('expense_cifs', 'code')->ignore($this->editingId),
            ],
            'cifSortOrder' => 'integer|min:0',
        ]);

        $data = [
            'code' => $normalized,
            'sort_order' => $this->cifSortOrder,
        ];

        if ($this->editingId) {
            ExpenseCif::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
        } else {
            ExpenseCif::create($data);
            $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
        }

        $this->showModal = false;
        $this->resetCifForm();
    }

    public function confirmDelete(string $kind, int $id): void
    {
        Gate::authorize('expenses.edit');
        $this->deleteTarget = $kind.':'.$id;
        $this->showDeleteModal = true;
    }

    public function openImport(string $target): void
    {
        Gate::authorize('expenses.edit');
        $this->importTarget = in_array($target, ['providers', 'cifs'], true) ? $target : 'providers';
        $this->importFile = null;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function processImport(): void
    {
        Gate::authorize('expenses.edit');

        $this->validate([
            'importFile' => 'required|file|mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream',
        ]);

        $data = Excel::toArray(null, $this->importFile->getRealPath());
        if (empty($data) || empty($data[0])) {
            $this->dispatch('notify', type: 'error', message: __('app.no_records_imported'));
            $this->showImportModal = false;

            return;
        }

        $rows = $data[0];
        [$columnMap, $startIndex] = $this->detectImportColumns($rows, $this->importTarget);

        $result = DB::transaction(function () use ($rows, $columnMap, $startIndex): array {
            return $this->importTarget === 'providers'
                ? $this->importProvidersFromRows($rows, $columnMap, $startIndex)
                : $this->importCifsFromRows($rows, $columnMap, $startIndex);
        });

        $this->showImportModal = false;
        $this->importFile = null;

        if (($result['rows'] ?? 0) === 0) {
            $this->dispatch('notify', type: 'error', message: __('app.no_records_imported'));

            return;
        }

        $message = $this->importTarget === 'providers'
            ? __('app.providers_import_summary', [
                'providers' => $result['providers'],
                'cifs' => $result['cifs'],
            ])
            : __('app.cifs_import_summary', [
                'count' => $result['cifs'],
            ]);

        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function delete(): void
    {
        Gate::authorize('expenses.edit');
        if ($this->deleteTarget === '') {
            return;
        }
        [$kind, $idStr] = explode(':', $this->deleteTarget, 2);
        $id = (int) $idStr;
        if ($kind === 'provider') {
            ExpenseProvider::findOrFail($id)->delete();
        } elseif ($kind === 'cif') {
            ExpenseCif::findOrFail($id)->delete();
        }
        $this->showDeleteModal = false;
        $this->deleteTarget = '';
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    private function resetProviderForm(): void
    {
        $this->providerName = '';
        $this->providerSortOrder = 0;
        $this->providerExpenseCifId = '';
        $this->providerNewCifCode = '';
        $this->editingId = null;
        $this->resetValidation();
    }

    private function resetCifForm(): void
    {
        $this->cifCode = '';
        $this->cifSortOrder = 0;
        $this->editingId = null;
        $this->resetValidation();
    }

    private function detectImportColumns(array $rows, string $target): array
    {
        $headerRow = $rows[0] ?? [];
        $normalizedHeaders = collect($headerRow)
            ->map(fn ($value) => $this->normalizeImportHeader((string) $value))
            ->all();

        $map = [
            'name' => $this->findHeaderIndex($normalizedHeaders, [
                'nombre',
                'nombre razon social',
                'razon social',
                'proveedor',
                'provider',
                'vendor',
            ]),
            'cif' => $this->findHeaderIndex($normalizedHeaders, [
                'cif',
                'nif',
                'tax id',
                'taxid',
            ]),
            'sort_order' => $this->findHeaderIndex($normalizedHeaders, [
                'orden',
                'sort order',
                'order',
            ]),
        ];

        $hasHeader = in_array(true, array_map(static fn ($index) => $index !== null, $map), true);

        if (! $hasHeader) {
            return [[], 0];
        }

        if ($target === 'cifs' && $map['cif'] === null) {
            $map['cif'] = 0;
        }

        return [$map, 1];
    }

    private function findHeaderIndex(array $normalizedHeaders, array $needles): ?int
    {
        foreach ($normalizedHeaders as $index => $header) {
            if ($header === '') {
                continue;
            }

            foreach ($needles as $needle) {
                if ($header === $needle || str_contains($header, $needle)) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function normalizeImportHeader(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', Str::lower(Str::ascii($value))));
    }

    private function importProvidersFromRows(array $rows, array $columnMap, int $startIndex): array
    {
        $providerOrder = (int) ExpenseProvider::max('sort_order');
        $cifOrder = (int) ExpenseCif::max('sort_order');
        $processedRows = 0;
        $providerChanges = 0;
        $cifChanges = 0;

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            $name = $this->extractImportedProviderName($row, $columnMap);
            $code = $this->extractImportedCifCode($row, $columnMap);

            if ($name === '') {
                continue;
            }

            $processedRows++;
            $cifId = null;

            if ($code !== '') {
                $cif = ExpenseCif::query()->where('code', $code)->first();
                if (! $cif) {
                    $cif = ExpenseCif::create([
                        'code' => $code,
                        'sort_order' => ++$cifOrder,
                    ]);
                    $cifChanges++;
                }
                $cifId = $cif->id;
            }

            $provider = ExpenseProvider::query()->where('name', $name)->first();
            $sortOrder = $this->extractImportedSortOrder($row, $columnMap) ?? ++$providerOrder;

            if (! $provider) {
                ExpenseProvider::create([
                    'name' => $name,
                    'sort_order' => $sortOrder,
                    'expense_cif_id' => $cifId,
                ]);
                $providerChanges++;
                continue;
            }

            $updates = [];
            if ($cifId !== null && $provider->expense_cif_id !== $cifId) {
                $updates['expense_cif_id'] = $cifId;
            }
            if (isset($columnMap['sort_order']) && $sortOrder !== $provider->sort_order) {
                $updates['sort_order'] = $sortOrder;
            }

            if ($updates !== []) {
                $provider->update($updates);
                $providerChanges++;
            }
        }

        return [
            'rows' => $processedRows,
            'providers' => $providerChanges,
            'cifs' => $cifChanges,
        ];
    }

    private function importCifsFromRows(array $rows, array $columnMap, int $startIndex): array
    {
        $cifOrder = (int) ExpenseCif::max('sort_order');
        $processedRows = 0;
        $cifChanges = 0;

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            $code = $this->extractImportedCifCode($row, $columnMap);
            if ($code === '') {
                continue;
            }

            $processedRows++;
            $sortOrder = $this->extractImportedSortOrder($row, $columnMap) ?? ++$cifOrder;
            $cif = ExpenseCif::query()->where('code', $code)->first();

            if (! $cif) {
                ExpenseCif::create([
                    'code' => $code,
                    'sort_order' => $sortOrder,
                ]);
                $cifChanges++;
                continue;
            }

            if (isset($columnMap['sort_order']) && $sortOrder !== $cif->sort_order) {
                $cif->update(['sort_order' => $sortOrder]);
                $cifChanges++;
            }
        }

        return [
            'rows' => $processedRows,
            'cifs' => $cifChanges,
        ];
    }

    private function extractImportedProviderName(array $row, array $columnMap): string
    {
        $candidate = $this->cellValue($row, $columnMap['name'] ?? null);
        if ($candidate === '' && array_key_exists(1, $row)) {
            $candidate = $this->cellValue($row, 1);
        }
        if ($candidate === '' && ! array_key_exists(1, $row)) {
            $candidate = $this->cellValue($row, 0);
        }

        return trim($candidate);
    }

    private function extractImportedCifCode(array $row, array $columnMap): string
    {
        $candidate = $this->cellValue($row, $columnMap['cif'] ?? null);
        if ($candidate === '' && array_key_exists(1, $row)) {
            $candidate = $this->cellValue($row, 0);
        }
        if ($candidate === '' && ! array_key_exists(1, $row)) {
            $candidate = $this->cellValue($row, 0);
        }

        return mb_strtoupper(trim($candidate));
    }

    private function extractImportedSortOrder(array $row, array $columnMap): ?int
    {
        $value = $this->cellValue($row, $columnMap['sort_order'] ?? null);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function cellValue(array $row, ?int $index): string
    {
        if ($index === null) {
            return '';
        }

        return trim((string) ($row[$index] ?? ''));
    }

    public function render()
    {
        $providersQuery = ExpenseProvider::query()->with('cif')->orderBy('sort_order')->orderBy('name');
        if ($this->searchProviders !== '') {
            $s = '%'.$this->searchProviders.'%';
            $providersQuery->where('name', 'like', $s);
        }
        $providers = $providersQuery->get();

        $cifsQuery = ExpenseCif::query()->orderBy('sort_order')->orderBy('code');
        if ($this->searchCifs !== '') {
            $s = '%'.$this->searchCifs.'%';
            $cifsQuery->where('code', 'like', $s);
        }
        $cifs = $cifsQuery->get();

        return view('livewire.expenses.expense-listado-config-page', [
            'providers' => $providers,
            'cifs' => $cifs,
            'cifsForProviderSelect' => ExpenseCif::query()->orderBy('sort_order')->orderBy('code')->get(['id', 'code']),
        ])->layout('layouts.app');
    }
}
