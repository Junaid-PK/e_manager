<?php

namespace App\Services;

use App\Models\Worker;
use App\Models\WorkerImport;
use App\Models\WorkerImportEntry;
use Maatwebsite\Excel\Facades\Excel;

class ImportWorkerAction
{
    /**
     * Import workers from Excel file and track changes
     *
     * @param  string  $filePath  Path to the uploaded Excel file
     * @param  array  $columnMap  Mapping of field names to column indices
     * @param  string|null  $fileName  Original file name for tracking
     * @return array Import statistics
     */
    public function execute(string $filePath, array $columnMap, ?string $fileName = null): array
    {
        $data = Excel::toArray(null, $filePath);

        if (empty($data) || empty($data[0])) {
            return [
                'imported' => 0,
                'new' => 0,
                'active' => 0,
                'removed' => 0,
                'skipped' => 0,
                'errors' => [],
            ];
        }

        $sheet = $data[0];
        $rows = array_slice($sheet, 1);
        $presentWorkerIds = [];
        $skippedNies = [];
        $errors = [];
        $newCount = 0;
        $skippedCount = 0;

        // Create import record
        $workerImport = WorkerImport::create([
            'file_name' => $fileName,
            'total_rows' => count($rows),
        ]);

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapRow($row, $columnMap);

                $fullName = trim($mapped['full_name'] ?? '');
                if (empty($fullName)) {
                    continue;
                }

                $nie = trim($mapped['nie'] ?? '');
                $bankAccount = trim($mapped['bank_account'] ?? '');
                $rate = $this->parseRate($mapped['rate'] ?? null);

                // Check if this row is a duplicate (same NIE or bank account as an existing worker)
                $existingWorker = $this->findExistingWorker($nie, $bankAccount);

                if ($existingWorker) {
                    // Skip duplicate worker - do not update
                    $skippedNies[] = $nie ?: $bankAccount;
                    $skippedCount++;
                    $presentWorkerIds[] = $existingWorker->id;

                    // Create import entry to track it was skipped
                    WorkerImportEntry::create([
                        'worker_import_id' => $workerImport->id,
                        'worker_id' => $existingWorker->id,
                        'full_name' => $fullName,
                        'nie' => $nie,
                        'bank_account' => $bankAccount,
                        'status_at_import' => 'skipped',
                    ]);

                    continue;
                }

                $now = now();

                // New worker
                $createData = [
                    'full_name' => $fullName,
                    'nie' => $nie ?: null,
                    'bank_account' => $bankAccount ?: null,
                    'import_status' => 'new',
                    'first_imported_at' => $now,
                    'last_imported_at' => $now,
                ];

                if ($rate > 0) {
                    $createData['rate'] = $rate;
                }

                $worker = Worker::create($createData);
                $presentWorkerIds[] = $worker->id;
                $newCount++;

                // Create import entry
                WorkerImportEntry::create([
                    'worker_import_id' => $workerImport->id,
                    'worker_id' => $worker->id,
                    'full_name' => $fullName,
                    'nie' => $nie,
                    'bank_account' => $bankAccount,
                    'status_at_import' => 'new',
                ]);
            } catch (\Exception $e) {
                $errors[] = __('app.row').' '.($index + 2).': '.$e->getMessage();
            }
        }

        // Detect workers that were previously imported but are missing from this sheet
        $presentWorkerIds = array_unique($presentWorkerIds);
        $removedWorkers = Worker::whereNotNull('last_imported_at')
            ->whereNotIn('id', $presentWorkerIds)
            ->get();

        $removedCount = 0;
        foreach ($removedWorkers as $removedWorker) {
            $removedWorker->update(['import_status' => 'removed']);

            WorkerImportEntry::create([
                'worker_import_id' => $workerImport->id,
                'worker_id' => $removedWorker->id,
                'full_name' => $removedWorker->full_name,
                'nie' => $removedWorker->nie,
                'bank_account' => $removedWorker->bank_account,
                'status_at_import' => 'removed',
            ]);

            $removedCount++;
        }

        // Update import counts
        $workerImport->update([
            'new_count' => $newCount,
            'active_count' => 0,
            'removed_count' => $removedCount,
        ]);

        return [
            'imported' => $newCount,
            'new' => $newCount,
            'active' => 0,
            'removed' => $removedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Find existing worker by NIE or bank account
     */
    private function findExistingWorker(string $nie, string $bankAccount): ?Worker
    {
        if (! empty($nie)) {
            $worker = Worker::where('nie', $nie)->first();
            if ($worker) {
                return $worker;
            }
        }

        if (! empty($bankAccount)) {
            $worker = Worker::where('bank_account', $bankAccount)->first();
            if ($worker) {
                return $worker;
            }
        }

        return null;
    }

    /**
     * Map row data based on column mapping
     */
    private function mapRow(array $row, array $columnMap): array
    {
        $mapped = [];
        foreach ($columnMap as $field => $headerIndex) {
            if ($headerIndex !== null && $headerIndex !== '' && isset($row[$headerIndex])) {
                $mapped[$field] = trim((string) $row[$headerIndex]);
            }
        }

        return $mapped;
    }

    /**
     * Parse rate value from import
     */
    private function parseRate($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        $raw = str_replace([' ', '€', '$', "\xC2\xA0"], '', $raw);

        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (preg_match('/^\d{1,3}(,\d{3})*(\.\d{1,2})?$/', $raw)) {
            $raw = str_replace(',', '', $raw);
        }

        return is_numeric($raw) ? (float) $raw : 0;
    }
}
