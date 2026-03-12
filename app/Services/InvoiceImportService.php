<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceImportService
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

                $invoiceNumber = trim($mapped['invoice_number'] ?? '');
                if (empty($invoiceNumber)) {
                    continue;
                }

                $companyName = trim($mapped['company'] ?? '');
                $clientName = trim($mapped['client'] ?? '');

                if (empty($companyName) && empty($clientName)) {
                    continue;
                }

                $companyId = $this->resolveOrCreateCompany($companyName);
                $clientId = $this->resolveOrCreateClient($clientName);

                if (!$companyId || !$clientId) {
                    $errors[] = __('app.row') . ' ' . ($index + 2) . ': ' . __('app.company') . '/' . __('app.client') . ' ' . __('app.required');
                    continue;
                }

                $amount = $this->parseAmount($mapped['amount'] ?? null) ?? 0;
                $ivaAmount = $this->parseAmount($mapped['iva_amount'] ?? null) ?? 0;
                $retentionAmount = $this->parseAmount($mapped['retention_amount'] ?? null) ?? 0;

                $totalRaw = $mapped['total'] ?? null;
                $total = $this->isFormula($totalRaw)
                    ? round($amount + $ivaAmount - $retentionAmount, 2)
                    : ($this->parseAmount($totalRaw) ?? round($amount + $ivaAmount - $retentionAmount, 2));

                $amountPaid = $this->parseAmount($mapped['amount_paid'] ?? null) ?? 0;
                $amountRemaining = $this->parseAmount($mapped['amount_remaining'] ?? null) ?? max(0, round($total - $amountPaid, 2));

                $status = $this->resolveStatus($mapped['status'] ?? null);
                $paymentType = $this->resolvePaymentType($mapped['payment_type'] ?? null);

                $monthValue = $this->parseMonth($mapped['month'] ?? null);
                $projectId = $this->resolveProjectId($mapped['project_id'] ?? null);

                Invoice::create([
                    'company_id' => $companyId,
                    'client_id' => $clientId,
                    'project_id' => $projectId,
                    'invoice_number' => $invoiceNumber,
                    'month' => $monthValue,
                    'date_issued' => $this->parseDate($mapped['date_issued'] ?? null) ?? $this->parseDate($mapped['month'] ?? null) ?? now()->format('Y-m-d'),
                    'date_due' => $this->parseDate($mapped['date_due'] ?? null),
                    'bank_date' => $this->parseDate($mapped['bank_date'] ?? null),
                    'bank_name' => trim($mapped['bank_name'] ?? '') ?: null,
                    'amount' => $amount,
                    'iva_amount' => $ivaAmount,
                    'iva_rate' => $amount > 0 && $ivaAmount > 0 ? round($ivaAmount / $amount * 100, 2) : 21,
                    'retention_amount' => $retentionAmount,
                    'retention_rate' => $amount > 0 && $retentionAmount > 0 ? round($retentionAmount / $amount * 100, 2) : 0,
                    'total' => $total,
                    'amount_paid' => $amountPaid,
                    'amount_remaining' => $amountRemaining,
                    'status' => $status,
                    'payment_type' => $paymentType,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = __('app.row') . ' ' . ($index + 2) . ': ' . $e->getMessage();
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

    private function resolveOrCreateCompany(?string $name): ?int
    {
        if (!$name) return null;

        $company = Company::where('name', $name)->first()
            ?? Company::where('name', 'like', "%{$name}%")->first()
            ?? Company::whereRaw('? LIKE CONCAT(\'%\', name, \'%\')', [$name])->first();

        if (!$company) {
            $company = Company::create(['name' => $name]);
        }

        return $company->id;
    }

    private function resolveOrCreateClient(?string $name): ?int
    {
        if (!$name) return null;

        $client = Client::where('name', $name)->first()
            ?? Client::where('name', 'like', "%{$name}%")->first()
            ?? Client::whereRaw('? LIKE CONCAT(\'%\', name, \'%\')', [$name])->first();

        if (!$client) {
            $client = Client::create(['name' => $name]);
        }

        return $client->id;
    }

    private function resolveProjectId(?string $value): ?int
    {
        if ($value === null || trim($value) === '') return null;
        $value = trim($value);
        $project = Project::where('name', $value)->first()
            ?? Project::where('code', $value)->first()
            ?? Project::where('name', 'like', "%{$value}%")->first()
            ?? Project::where('code', 'like', "%{$value}%")->first();
        return $project?->id;
    }

    private function isFormula(?string $value): bool
    {
        if ($value === null) return false;
        return str_starts_with(trim($value), '=');
    }

    private function parseMonth(?string $value): ?string
    {
        if (!$value) return null;

        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value);
                return $date->format('M-Y');
            } catch (\Exception) {
                return $value;
            }
        }

        return $value;
    }

    private function resolveStatus(?string $value): string
    {
        if (!$value) return 'pending';
        $value = strtolower(trim($value));

        $map = [
            'yes' => 'paid', 'si' => 'paid', 'sí' => 'paid',
            'paid' => 'paid', 'pagado' => 'paid', 'pagada' => 'paid',
            'no' => 'pending', 'pending' => 'pending', 'pendiente' => 'pending',
            'partial' => 'partial', 'parcial' => 'partial',
            'overdue' => 'overdue', 'vencido' => 'overdue', 'vencida' => 'overdue',
            'cancelled' => 'cancelled', 'cancelado' => 'cancelled', 'cancelada' => 'cancelled',
        ];

        return $map[$value] ?? 'pending';
    }

    private function resolvePaymentType(?string $value): ?string
    {
        if (!$value) return null;
        $value = strtolower(trim($value));

        $map = [
            'confirming' => 'confirming',
            'cheque' => 'cheque',
            'transfer' => 'transfer', 'transferencia' => 'transfer',
            'cash' => 'cash', 'efectivo' => 'cash',
        ];

        return $map[$value] ?? 'other';
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value) return null;

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'm/d/Y', 'M-y', 'M-Y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) return $date->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value)->format('Y-m-d');
        }

        return null;
    }

    private function parseAmount(?string $value): ?float
    {
        if ($value === null || $value === '') return null;

        if (is_numeric($value)) {
            return abs((float) $value);
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
        return $amount != 0 ? abs($amount) : 0;
    }
}
