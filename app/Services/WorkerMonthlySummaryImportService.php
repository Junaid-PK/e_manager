<?php

namespace App\Services;

use App\Models\MonthlyPeriod;
use App\Models\Worker;
use App\Models\WorkerMonthlySummary;
use Maatwebsite\Excel\Facades\Excel;

class WorkerMonthlySummaryImportService
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

    public function importMappedData(string $filePath, array $columnMap): array
    {
        $data = Excel::toArray(null, $filePath);

        if (empty($data) || empty($data[0])) {
            return ['imported' => 0, 'errors' => []];
        }

        $sheet = $data[0];
        $rows = array_slice($sheet, 1);
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapRow($row, $columnMap);

                $periodCode = trim($mapped['period_code'] ?? '');
                $workerName = trim($mapped['worker'] ?? '');

                if (empty($periodCode) || empty($workerName)) {
                    continue;
                }

                $period = MonthlyPeriod::where('period_code', $periodCode)->first();
                if (! $period) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.period').' "'.$periodCode.'" '.__('app.not_found');
                    continue;
                }

                $worker = Worker::where('full_name', $workerName)->first()
                    ?? Worker::where('full_name', 'like', "%{$workerName}%")->first();
                if (! $worker) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.worker').' "'.$workerName.'" '.__('app.not_found');
                    continue;
                }

                $totalAmount = $this->parseAmount($mapped['total_amount'] ?? null) ?? 0;
                $paidAmount = $this->parseAmount($mapped['paid_amount'] ?? null) ?? 0;
                $totalHours = $this->parseAmount($mapped['total_hours'] ?? null) ?? 0;
                $payrollAmount = $this->parseAmount($mapped['payroll_amount'] ?? null) ?? 0;
                $advanceAmount = $this->parseAmount($mapped['advance_amount'] ?? null) ?? 0;
                $creditAmount = $this->parseAmount($mapped['credit_amount'] ?? null) ?? 0;
                $ticketAmount = $this->parseAmount($mapped['ticket_amount'] ?? null) ?? 0;

                WorkerMonthlySummary::updateOrCreate(
                    [
                        'monthly_period_id' => $period->id,
                        'worker_id' => $worker->id,
                    ],
                    [
                        'total_amount' => $totalAmount,
                        'paid_amount' => $paidAmount,
                        'total_hours' => $totalHours,
                        'payroll_amount' => $payrollAmount,
                        'advance_amount' => $advanceAmount,
                        'credit_amount' => $creditAmount,
                        'ticket_amount' => $ticketAmount,
                    ]
                );

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
