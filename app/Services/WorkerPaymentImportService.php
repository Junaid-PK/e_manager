<?php

namespace App\Services;

use App\Models\MonthlyPeriod;
use App\Models\ProjectMonth;
use App\Models\Worker;
use App\Models\WorkerPayment;
use Maatwebsite\Excel\Facades\Excel;

class WorkerPaymentImportService
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
                $projectMonthLabel = trim($mapped['project_month'] ?? '');
                $paymentDate = trim($mapped['payment_date'] ?? '');
                $paymentType = trim($mapped['payment_type'] ?? '');
                $amount = $this->parseAmount($mapped['amount'] ?? null);

                if (empty($periodCode) || empty($workerName) || empty($paymentDate) || $amount === null) {
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

                $projectMonthId = null;
                if (!empty($projectMonthLabel)) {
                    $projectMonth = ProjectMonth::with(['monthlyPeriod', 'client', 'project'])
                        ->whereHas('monthlyPeriod', fn ($q) => $q->where('period_code', $periodCode))
                        ->where(function ($q) use ($projectMonthLabel) {
                            $q->whereHas('client', fn ($sq) => $sq->where('name', 'like', "%{$projectMonthLabel}%"))
                              ->orWhereHas('project', fn ($sq) => $sq->where('name', 'like', "%{$projectMonthLabel}%"));
                        })
                        ->first();

                    if (! $projectMonth) {
                        $projectMonth = ProjectMonth::with(['monthlyPeriod', 'client', 'project'])
                            ->whereHas('monthlyPeriod', fn ($q) => $q->where('period_code', $periodCode))
                            ->first();
                    }

                    $projectMonthId = $projectMonth?->id;
                }

                if (! $projectMonthId) {
                    $projectMonth = ProjectMonth::where('monthly_period_id', $period->id)->first();
                    $projectMonthId = $projectMonth?->id;
                }

                if (! $projectMonthId) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.project_month').' '.__('app.not_found');
                    continue;
                }

                $normalizedType = $this->normalizePaymentType($paymentType);
                if (! $normalizedType) {
                    $normalizedType = 'bank';
                }

                $parsedDate = $this->parseDate($paymentDate);
                if (! $parsedDate) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.payment_date').' "'.$paymentDate.'" '.__('app.not_found');
                    continue;
                }

                $reference = trim($mapped['reference'] ?? '') ?: null;
                $notes = trim($mapped['notes'] ?? '') ?: null;

                $existing = WorkerPayment::where([
                    'worker_id' => $worker->id,
                    'monthly_period_id' => $period->id,
                    'project_month_id' => $projectMonthId,
                    'payment_date' => $parsedDate,
                    'payment_type' => $normalizedType,
                    'amount' => $amount,
                ])->when($reference, fn ($q) => $q->where('reference', $reference))
                ->first();

                if ($existing) {
                    $existing->update([
                        'reference' => $reference,
                        'notes' => $notes,
                    ]);
                } else {
                    WorkerPayment::create([
                        'worker_id' => $worker->id,
                        'monthly_period_id' => $period->id,
                        'project_month_id' => $projectMonthId,
                        'payment_date' => $parsedDate,
                        'payment_type' => $normalizedType,
                        'amount' => $amount,
                        'reference' => $reference,
                        'notes' => $notes,
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

    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if (empty($value)) {
            return null;
        }

        $formats = [
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'd.m.Y',
            'm/d/Y',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    private function normalizePaymentType(string $value): ?string
    {
        $lower = mb_strtolower(trim($value));

        return match (true) {
            str_contains($lower, 'bank') || str_contains($lower, 'banco') || str_contains($lower, 'transfer') || str_contains($lower, 'transferencia') => 'bank',
            str_contains($lower, 'cash') || str_contains($lower, 'efectivo') => 'cash',
            str_contains($lower, 'advance') || str_contains($lower, 'anticipo') => 'advance',
            str_contains($lower, 'ticket') || str_contains($lower, 'vale') => 'ticket',
            str_contains($lower, 'adjustment') || str_contains($lower, 'ajuste') => 'adjustment',
            default => null,
        };
    }
}
