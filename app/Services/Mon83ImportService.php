<?php

namespace App\Services;

use App\Models\Worker;
use App\Models\WorkerProjectEntry;
use Maatwebsite\Excel\Facades\Excel;

class Mon83ImportService
{
    public function parseFile(string $filePath): array
    {
        $data = Excel::toArray(null, $filePath);

        if (empty($data) || empty($data[0])) {
            return ['headers' => [], 'rows' => []];
        }

        $sheet = $data[0];
        $headers = array_map('trim', $sheet[0] ?? []);
        $rows = array_slice($sheet, 1, 100);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function importMappedData(string $filePath, array $columnMap, int $projectMonthId): array
    {
        $data = Excel::toArray(null, $filePath);

        if (empty($data) || empty($data[0])) {
            return ['imported' => 0, 'errors' => []];
        }

        $sheet = $data[0];
        $rows = array_slice($sheet, 1);
        $imported = 0;
        $errors = [];

        $nieIdx = $columnMap['nie'] ?? null;

        if ($nieIdx === null || $nieIdx === '') {
            return ['imported' => 0, 'errors' => [__('app.nie_column_required')]];
        }

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapRow($row, $columnMap);

                $nie = trim($mapped['nie'] ?? '');

                if (empty($nie)) {
                    continue;
                }

                $worker = Worker::where('nie', $nie)->first();
                if (! $worker) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.worker_with_nie').' "'.$nie.'" '.__('app.not_found');
                    continue;
                }

                $existingEntry = WorkerProjectEntry::where('project_month_id', $projectMonthId)
                    ->where('worker_id', $worker->id)
                    ->first();

                $socialSecurity = $this->parseAmount($mapped['social_security'] ?? null) ?? 0;
                $hours = $this->parseAmount($mapped['hours'] ?? null) ?? 0;
                $days = $this->parseAmount($mapped['days'] ?? null) ?? 0;
                $rate = $this->parseAmount($mapped['rate'] ?? null) ?? 0;

                if ($existingEntry) {
                    $existingEntry->update([
                        'social_security' => $socialSecurity,
                        'hours' => $hours,
                        'days' => $days,
                        'rate' => $rate,
                    ]);
                } else {
                    WorkerProjectEntry::create([
                        'project_month_id' => $projectMonthId,
                        'worker_id' => $worker->id,
                        'social_security' => $socialSecurity,
                        'hours' => $hours,
                        'days' => $days,
                        'rate' => $rate,
                        'special_note' => null,
                    ]);
                }

                $imported++;
            } catch (\Exception $e) {
                $errors[] = __('app.row').' '.($index + 2).': '.$e->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

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

    private function parseAmount($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
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

        return is_numeric($raw) ? (float) $raw : null;
    }
}
