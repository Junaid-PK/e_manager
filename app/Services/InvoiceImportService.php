<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
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

                $companyId = $this->resolveCompany($mapped['company'] ?? null);
                $clientId = $this->resolveClient($mapped['client'] ?? null);

                if (!$companyId || !$clientId) {
                    $errors[] = __('app.row') . ' ' . ($index + 2) . ': ' . __('app.company') . '/' . __('app.client') . ' not found';
                    continue;
                }

                $amount = $this->parseAmount($mapped['amount'] ?? null) ?? 0;
                $ivaAmount = $this->parseAmount($mapped['iva_amount'] ?? null) ?? 0;
                $retentionAmount = $this->parseAmount($mapped['retention_amount'] ?? null) ?? 0;
                $total = $this->parseAmount($mapped['total'] ?? null) ?? round($amount + $ivaAmount - $retentionAmount, 2);
                $amountPaid = $this->parseAmount($mapped['amount_paid'] ?? null) ?? 0;
                $amountRemaining = $this->parseAmount($mapped['amount_remaining'] ?? null) ?? max(0, round($total - $amountPaid, 2));

                $status = $this->resolveStatus($mapped['status'] ?? null);
                $paymentType = $this->resolvePaymentType($mapped['payment_type'] ?? null);

                $invoiceNumber = trim($mapped['invoice_number'] ?? '');
                if (empty($invoiceNumber)) {
                    continue;
                }

                Invoice::create([
                    'company_id' => $companyId,
                    'client_id' => $clientId,
                    'invoice_number' => $invoiceNumber,
                    'month' => trim($mapped['month'] ?? '') ?: null,
                    'date_issued' => $this->parseDate($mapped['date_issued'] ?? null) ?? now()->format('Y-m-d'),
                    'date_due' => $this->parseDate($mapped['date_due'] ?? null),
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

    private function resolveCompany(?string $name): ?int
    {
        if (!$name) return null;
        $company = Company::where('name', 'like', "%{$name}%")->first();
        return $company?->id;
    }

    private function resolveClient(?string $name): ?int
    {
        if (!$name) return null;
        $client = Client::where('name', 'like', "%{$name}%")->first();
        return $client?->id;
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
        $value = str_replace([' ', '€', '\u{a0}'], '', $value);

        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        $amount = (float) $value;
        return $amount != 0 ? abs($amount) : 0;
    }
}
