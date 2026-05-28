<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MonthlyPeriod;
use App\Models\Project;
use App\Models\ProjectInvoice;
use App\Models\ProjectMonth;
use Maatwebsite\Excel\Facades\Excel;

class ProjectInvoiceImportService
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
                $clientName = trim($mapped['client'] ?? '');
                $projectName = trim($mapped['project'] ?? '');

                if (empty($periodCode) || empty($clientName) || empty($projectName)) {
                    continue;
                }

                $period = MonthlyPeriod::where('period_code', $periodCode)->first();
                if (! $period) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.period').' "'.$periodCode.'" '.__('app.not_found');
                    continue;
                }

                $client = Client::where('name', $clientName)->first()
                    ?? Client::where('name', 'like', "%{$clientName}%")->first();
                if (! $client) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.client').' "'.$clientName.'" '.__('app.not_found');
                    continue;
                }

                $project = Project::where('name', $projectName)->first()
                    ?? Project::where('name', 'like', "%{$projectName}%")->first();
                if (! $project) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.project').' "'.$projectName.'" '.__('app.not_found');
                    continue;
                }

                $projectMonth = ProjectMonth::where('monthly_period_id', $period->id)
                    ->where('client_id', $client->id)
                    ->where('project_id', $project->id)
                    ->first();

                if (! $projectMonth) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.project_month').' '.__('app.not_found');
                    continue;
                }

                $invoiceNo = trim($mapped['invoice_no'] ?? '') ?: null;
                $invoiceDate = $this->parseDate($mapped['invoice_date'] ?? null);
                $estimatedAmount = $this->parseAmount($mapped['estimated_amount'] ?? null) ?? 0;
                $actualAmount = $this->parseAmount($mapped['actual_amount'] ?? null) ?? 0;
                $status = $this->normalizeStatus($mapped['status'] ?? '');
                $notes = trim($mapped['notes'] ?? '') ?: null;

                ProjectInvoice::create([
                    'project_month_id' => $projectMonth->id,
                    'invoice_no' => $invoiceNo,
                    'invoice_date' => $invoiceDate,
                    'estimated_amount' => $estimatedAmount,
                    'actual_amount' => $actualAmount,
                    'status' => $status,
                    'notes' => $notes,
                ]);

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

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $excelEpoch = \DateTime::createFromFormat('Y-m-d', '1900-01-01');
            if ($excelEpoch) {
                $excelEpoch->modify('+'.((int) $value - 2).' days');
                return $excelEpoch->format('Y-m-d');
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim((string) $value));
            if ($date && $date->format($format) === trim((string) $value)) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime(trim((string) $value));
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    private function normalizeStatus(string $value): string
    {
        $lower = mb_strtolower(trim($value));
        return match ($lower) {
            'draft', 'borrador', 'provisional' => 'draft',
            'sent', 'enviado', 'emitida', 'emitido' => 'sent',
            'paid', 'pagado', 'cobrado', 'pagada' => 'paid',
            'partial', 'parcial', 'parcialmente' => 'partial',
            'cancelled', 'cancelado', 'anulado', 'cancelada', 'anulada' => 'cancelled',
            default => 'draft',
        };
    }
}
