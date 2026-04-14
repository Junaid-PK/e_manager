<?php

namespace App\Services;

use App\Models\MovementCategory;
use App\Models\MovementType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

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

        $categories = MovementCategory::query()->orderBy('sort_order')->orderBy('name')->get();
        $types = MovementType::query()->orderBy('sort_order')->orderBy('name')->get();

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapRow($row, $headers, $columnMap);
                if (empty($mapped['date']) || empty($mapped['concept'])) {
                    continue;
                }

                $deposit = null;
                $withdrawal = null;
                if (isset($mapped['amount']) && $mapped['amount'] !== '') {
                    $split = $this->parseSignedAmount($mapped['amount']);
                    $deposit = $split['deposit'];
                    $withdrawal = $split['withdrawal'];
                } else {
                    $deposit = $this->parseAmount($mapped['deposit'] ?? null);
                    $withdrawal = $this->parseAmount($mapped['withdrawal'] ?? null);
                }

                $resolvedCategory = $this->resolveImportedCategory($mapped['category'] ?? null, $categories);
                $resolvedType = $this->resolveImportedType($mapped['type'] ?? null, $types);

                \App\Models\BankMovement::create([
                    'bank_account_id' => $bankAccountId,
                    'date' => $this->parseDate($mapped['date']),
                    'value_date' => isset($mapped['value_date']) ? $this->parseDate($mapped['value_date']) : null,
                    'type' => $resolvedType,
                    'concept' => $mapped['concept'],
                    'beneficiary' => $mapped['beneficiary'] ?? null,
                    'reference' => $mapped['reference'] ?? null,
                    'deposit' => $deposit,
                    'withdrawal' => $withdrawal,
                    'balance' => 0,
                    'category' => $resolvedCategory,
                    'import_source' => 'csv',
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = 'Row '.($index + 2).': '.$e->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Only accept category labels that exist in movement_categories (match by name or slug, case-insensitive).
     * Unknown values are skipped (null).
     */
    private function resolveImportedCategory(?string $raw, Collection $categories): ?string
    {
        $t = trim((string) ($raw ?? ''));
        if ($t === '') {
            return null;
        }
        $lower = mb_strtolower($t);
        foreach ($categories as $c) {
            if (mb_strtolower(trim((string) $c->name)) === $lower) {
                return $c->name;
            }
            if (mb_strtolower((string) $c->slug) === $lower) {
                return $c->name;
            }
        }

        return null;
    }

    /**
     * Only accept types that exist in movement_types (match by slug or name, case-insensitive).
     * Unknown values are skipped (default type "other").
     */
    private function resolveImportedType(?string $raw, Collection $types): string
    {
        $t = trim((string) ($raw ?? ''));
        if ($t === '') {
            return 'other';
        }
        $lower = mb_strtolower($t);
        foreach ($types as $ty) {
            if (mb_strtolower((string) $ty->slug) === $lower) {
                return $ty->slug;
            }
            if (mb_strtolower(trim((string) $ty->name)) === $lower) {
                return $ty->slug;
            }
        }

        return 'other';
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

    public function parseSignedAmount(?string $value): array
    {
        $deposit = null;
        $withdrawal = null;
        if ($value === null || $value === '') {
            return ['deposit' => null, 'withdrawal' => null];
        }
        $raw = trim($value);
        $negative = preg_match('/^\-/', $raw) || preg_match('/^\(\s*[\d,.\s]+\s*\)$/', $raw);
        $value = str_replace([' ', '€', '$', "\xC2\xA0"], '', $raw);
        $value = preg_replace('/^[+\-]\s*/', '', $value);
        $value = trim($value, " \t\n\r\0\x0B()");
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d{1,3}(,\d{3})*(\.\d{1,2})?$/', $value)) {
            $value = str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
        $amount = (float) $value;
        if ($amount == 0) {
            return ['deposit' => null, 'withdrawal' => null];
        }
        if (is_numeric($raw) && (float) $raw < 0) {
            $negative = true;
        }
        $abs = abs($amount);
        if ($negative || $amount < 0) {
            $withdrawal = round($abs, 2);
        } else {
            $deposit = round($abs, 2);
        }

        return ['deposit' => $deposit, 'withdrawal' => $withdrawal];
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

        return $value;
    }

    private function parseAmount(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $amount = (float) $value;

            return $amount != 0 ? abs($amount) : null;
        }

        $value = str_replace([' ', '€', '$', "\xC2\xA0"], '', $value);

        if (preg_match('/^-?\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^-?\d{1,3}(,\d{3})*(\.\d{1,2})?$/', $value)) {
            $value = str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        $amount = (float) $value;

        return $amount != 0 ? abs($amount) : null;
    }
}
