<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MonthlyPeriod;
use App\Models\Project;
use App\Models\ProjectMonth;
use Maatwebsite\Excel\Facades\Excel;

class ProjectMonthImportService
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

                $totalNominal = $this->parseAmount($mapped['total_nominal'] ?? null) ?? 0;
                $totalSocialSecurity = $this->parseAmount($mapped['total_social_security'] ?? null) ?? 0;
                $totalExpenses = $this->parseAmount($mapped['total_expenses'] ?? null) ?? 0;
                $totalInvoiced = $this->parseAmount($mapped['total_invoiced'] ?? null) ?? 0;
                $estimatedInvoice = $this->parseAmount($mapped['estimated_invoice'] ?? null) ?? 0;
                $difference = $this->parseAmount($mapped['difference'] ?? null) ?? ($estimatedInvoice - $totalInvoiced);
                $totalHours = $this->parseAmount($mapped['total_hours'] ?? null) ?? 0;

                ProjectMonth::updateOrCreate(
                    [
                        'monthly_period_id' => $period->id,
                        'client_id' => $client->id,
                        'project_id' => $project->id,
                    ],
                    [
                        'sheet_code' => trim($mapped['sheet_code'] ?? '') ?: null,
                        'total_nominal' => $totalNominal,
                        'total_social_security' => $totalSocialSecurity,
                        'total_expenses' => $totalExpenses,
                        'total_invoiced' => $totalInvoiced,
                        'estimated_invoice' => $estimatedInvoice,
                        'difference' => $difference,
                        'total_hours' => $totalHours,
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
