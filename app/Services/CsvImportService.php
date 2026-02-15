<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class CsvImportService
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

    public function importMappedData(string $filePath, array $columnMap, int $bankAccountId): array
    {
        $data = Excel::toArray(null, $filePath);

        if (empty($data) || empty($data[0])) {
            return ['imported' => 0, 'errors' => []];
        }

        $sheet = $data[0];
        $headers = array_map('trim', $sheet[0] ?? []);
        $rows = array_slice($sheet, 1);
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapRow($row, $headers, $columnMap);
                if (empty($mapped['date']) || empty($mapped['concept'])) {
                    continue;
                }

                \App\Models\BankMovement::create([
                    'bank_account_id' => $bankAccountId,
                    'date' => $this->parseDate($mapped['date']),
                    'value_date' => isset($mapped['value_date']) ? $this->parseDate($mapped['value_date']) : null,
                    'type' => $mapped['type'] ?? 'other',
                    'concept' => $mapped['concept'],
                    'beneficiary' => $mapped['beneficiary'] ?? null,
                    'reference' => $mapped['reference'] ?? null,
                    'deposit' => $this->parseAmount($mapped['deposit'] ?? null),
                    'withdrawal' => $this->parseAmount($mapped['withdrawal'] ?? null),
                    'balance' => $this->parseAmount($mapped['balance'] ?? null) ?? 0,
                    'import_source' => 'csv',
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    private function mapRow(array $row, array $headers, array $columnMap): array
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
        if (!$value) return null;

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'm/d/Y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) return $date->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int)$value)->format('Y-m-d');
        }

        return $value;
    }

    private function parseAmount(?string $value): ?float
    {
        if ($value === null || $value === '') return null;
        $value = str_replace(['.', ',', ' '], ['', '.', ''], $value);
        $amount = (float) $value;
        return $amount != 0 ? abs($amount) : null;
    }
}
