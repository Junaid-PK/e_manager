<?php

namespace App\Services;

use App\Models\MonthlyPeriod;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyPeriodImportService
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

                $year = (int) ($mapped['year'] ?? 0);
                $month = (int) ($mapped['month'] ?? 0);

                if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
                    continue;
                }

                $periodCode = $mapped['period_code'] ?? sprintf('%04d-%02d', $year, $month);
                $label = $mapped['label'] ?? date('F Y', mktime(0, 0, 0, $month, 1, $year));
                $startDate = $this->parseDate($mapped['start_date'] ?? null) ?? sprintf('%04d-%02d-01', $year, $month);
                $endDate = $this->parseDate($mapped['end_date'] ?? null) ?? date('Y-m-t', strtotime($startDate));

                MonthlyPeriod::updateOrCreate(
                    ['period_code' => $periodCode],
                    [
                        'year' => $year,
                        'month' => $month,
                        'label' => $label,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
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

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'm/d/Y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value)->format('Y-m-d');
        }

        return null;
    }
}
