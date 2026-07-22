<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Support\Collection;
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

        $canonicalBankNames = BankAccount::query()
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->distinct()
            ->orderBy('bank_name')
            ->pluck('bank_name');

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

                if (! $companyId || ! $clientId) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.company').'/'.__('app.client').' '.__('app.required');

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
                $amountRemaining = $this->parseAmount($mapped['amount_remaining'] ?? null) ?? round($total - $amountPaid, 2);

                $status = $this->resolveImportedStatus($mapped['status'] ?? null);
                $paymentType = $this->resolveImportedPaymentType($mapped['payment_type'] ?? null);
                $bankNameResolved = $this->resolveImportedBankName($mapped['bank_name'] ?? null, $canonicalBankNames);

                $monthValue = $this->parseMonth($mapped['month'] ?? null);
                $projectId = $this->resolveProjectId($mapped['project_id'] ?? null, $companyId);
                \Log::info('Importing IVA', [
                    'raw_value' => $mapped['iva_amount'],
                    'parsed_value' => $ivaAmount,
                ]);
                Invoice::create([
                    'company_id' => $companyId,
                    'client_id' => $clientId,
                    'project_id' => $projectId,
                    'invoice_number' => $invoiceNumber,
                    'month' => $monthValue,
                    'date_issued' => $this->parseDate($mapped['date_issued'] ?? null) ?? $this->parseDate($mapped['month'] ?? null) ?? now()->format('Y-m-d'),
                    'date_due' => $this->parseDate($mapped['date_due'] ?? null),
                    'bank_date' => $this->parseDate($mapped['bank_date'] ?? null),
                    'bank_name' => $bankNameResolved,
                    'amount' => $amount,
                    'iva_amount' => $ivaAmount,
                    'iva_rate' => $amount != 0.0 && $ivaAmount != 0.0 ? round(abs($ivaAmount / $amount) * 100, 2) : 21,
                    'retention_amount' => $retentionAmount,
                    'retention_rate' => $amount != 0.0 && $retentionAmount != 0.0 ? round(abs($retentionAmount / $amount) * 100, 2) : 0,
                    'total' => $total,
                    'amount_paid' => $amountPaid,
                    'amount_remaining' => $amountRemaining,
                    'status' => $status,
                    'payment_type' => $paymentType,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = __('app.row').' '.($index + 2).': '.$e->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    public function syncMappedData(string $filePath, array $columnMap): array
    {
        $data = Excel::toArray(null, $filePath);

        if (empty($data) || empty($data[0])) {
            return ['imported' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => []];
        }

        $rows = array_slice($data[0], 1);
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $errors = [];
        $ownerId = (int) auth()->id();
        $canonicalBankNames = BankAccount::query()
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->distinct()
            ->orderBy('bank_name')
            ->pluck('bank_name');

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapRow($row, $columnMap);
                $invoiceNumber = trim($mapped['invoice_number'] ?? '');

                if ($invoiceNumber === '') {
                    continue;
                }

                $invoice = Invoice::query()
                    ->where('user_id', $ownerId)
                    ->whereRaw('LOWER(TRIM(invoice_number)) = ?', [mb_strtolower($invoiceNumber)])
                    ->first();

                $companyId = $this->mapped($columnMap, 'company')
                    ? $this->resolveOrCreateCompany(trim($mapped['company'] ?? ''))
                    : $invoice?->company_id;
                $clientId = $this->mapped($columnMap, 'client')
                    ? $this->resolveOrCreateClient(trim($mapped['client'] ?? ''))
                    : $invoice?->client_id;

                if (! $companyId || ! $clientId) {
                    $errors[] = __('app.row').' '.($index + 2).': '.__('app.company').'/'.__('app.client').' '.__('app.required');

                    continue;
                }

                $attributes = $this->syncAttributes(
                    $mapped,
                    $columnMap,
                    (int) $companyId,
                    (int) $clientId,
                    $invoice,
                    $canonicalBankNames,
                );

                if ($invoice) {
                    $invoice->fill($attributes);

                    if ($invoice->isDirty()) {
                        $invoice->save();
                        $updated++;
                    } else {
                        $unchanged++;
                    }

                    continue;
                }

                Invoice::create(array_merge([
                    'user_id' => $ownerId,
                    'company_id' => $companyId,
                    'client_id' => $clientId,
                    'project_id' => null,
                    'invoice_number' => $invoiceNumber,
                    'month' => null,
                    'date_issued' => $this->parseDate($mapped['month'] ?? null) ?? now()->format('Y-m-d'),
                    'date_due' => null,
                    'bank_date' => null,
                    'bank_name' => null,
                    'amount' => 0,
                    'iva_amount' => 0,
                    'iva_rate' => 21,
                    'retention_amount' => 0,
                    'retention_rate' => 0,
                    'total' => 0,
                    'amount_paid' => 0,
                    'amount_remaining' => 0,
                    'status' => 'pending',
                    'payment_type' => null,
                ], $attributes));
                $created++;
            } catch (\Throwable $e) {
                $errors[] = __('app.row').' '.($index + 2).': '.$e->getMessage();
            }
        }

        return [
            'imported' => $created + $updated,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'errors' => $errors,
        ];
    }

    private function syncAttributes(
        array $mapped,
        array $columnMap,
        int $companyId,
        int $clientId,
        ?Invoice $invoice,
        Collection $canonicalBankNames,
    ): array {
        $attributes = [];

        if ($this->mapped($columnMap, 'company')) {
            $attributes['company_id'] = $companyId;
        }
        if ($this->mapped($columnMap, 'client')) {
            $attributes['client_id'] = $clientId;
        }
        if ($this->mapped($columnMap, 'project_id')) {
            $attributes['project_id'] = $this->resolveProjectId($mapped['project_id'] ?? null, $companyId, $clientId);
        }
        if ($this->mapped($columnMap, 'month')) {
            $attributes['month'] = $this->parseMonth($mapped['month'] ?? null);
        }
        if ($this->mapped($columnMap, 'date_issued')) {
            $attributes['date_issued'] = $this->parseDate($mapped['date_issued'] ?? null)
                ?? $this->parseDate($mapped['month'] ?? null)
                ?? $invoice?->date_issued?->format('Y-m-d')
                ?? now()->format('Y-m-d');
        }
        if ($this->mapped($columnMap, 'date_due')) {
            $attributes['date_due'] = $this->parseDate($mapped['date_due'] ?? null);
        }
        if ($this->mapped($columnMap, 'bank_date')) {
            $attributes['bank_date'] = $this->parseDate($mapped['bank_date'] ?? null);
        }
        if ($this->mapped($columnMap, 'bank_name')) {
            $attributes['bank_name'] = $this->resolveImportedBankName($mapped['bank_name'] ?? null, $canonicalBankNames);
        }
        if ($this->mapped($columnMap, 'payment_type')) {
            $attributes['payment_type'] = $this->resolveImportedPaymentType($mapped['payment_type'] ?? null);
        }

        $amount = $this->mapped($columnMap, 'amount')
            ? ($this->parseAmount($mapped['amount'] ?? null) ?? 0)
            : (float) ($invoice?->amount ?? 0);
        $ivaAmount = $this->mapped($columnMap, 'iva_amount')
            ? ($this->parseAmount($mapped['iva_amount'] ?? null) ?? 0)
            : (float) ($invoice?->iva_amount ?? 0);
        $retentionAmount = $this->mapped($columnMap, 'retention_amount')
            ? ($this->parseAmount($mapped['retention_amount'] ?? null) ?? 0)
            : (float) ($invoice?->retention_amount ?? 0);

        if ($this->mapped($columnMap, 'amount')) {
            $attributes['amount'] = $amount;
        }
        if ($this->mapped($columnMap, 'iva_amount')) {
            $attributes['iva_amount'] = $ivaAmount;
        }
        if ($this->mapped($columnMap, 'retention_amount')) {
            $attributes['retention_amount'] = $retentionAmount;
        }
        if ($this->mapped($columnMap, 'amount') || $this->mapped($columnMap, 'iva_amount')) {
            $attributes['iva_rate'] = $amount != 0.0 && $ivaAmount != 0.0
                ? round(abs($ivaAmount / $amount) * 100, 2)
                : 21;
        }
        if ($this->mapped($columnMap, 'amount') || $this->mapped($columnMap, 'retention_amount')) {
            $attributes['retention_rate'] = $amount != 0.0 && $retentionAmount != 0.0
                ? round(abs($retentionAmount / $amount) * 100, 2)
                : 0;
        }
        if ($this->mapped($columnMap, 'total')) {
            $totalRaw = $mapped['total'] ?? null;
            $attributes['total'] = $this->isFormula($totalRaw)
                ? round($amount + $ivaAmount - $retentionAmount, 2)
                : ($this->parseAmount($totalRaw) ?? round($amount + $ivaAmount - $retentionAmount, 2));
        }
        if ($this->mapped($columnMap, 'amount_paid')) {
            $attributes['amount_paid'] = $this->parseAmount($mapped['amount_paid'] ?? null) ?? 0;
        }
        if ($this->mapped($columnMap, 'amount_remaining')) {
            $attributes['amount_remaining'] = $this->parseAmount($mapped['amount_remaining'] ?? null) ?? 0;
        }

        return $attributes;
    }

    private function mapped(array $columnMap, string $field): bool
    {
        return isset($columnMap[$field]) && $columnMap[$field] !== '';
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
        if (! $name) {
            return null;
        }

        $company = Company::where('name', $name)->first()
            ?? Company::where('name', 'like', "%{$name}%")->first()
            ?? Company::whereRaw('? LIKE CONCAT(\'%\', name, \'%\')', [$name])->first();

        if (! $company) {
            $company = Company::create(['name' => $name]);
        }

        return $company->id;
    }

    private function resolveOrCreateClient(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        $client = Client::where('name', $name)->first()
            ?? Client::where('name', 'like', "%{$name}%")->first()
            ?? Client::whereRaw('? LIKE CONCAT(\'%\', name, \'%\')', [$name])->first();

        if (! $client) {
            $client = Client::create(['name' => $name]);
        }

        return $client->id;
    }

    private function resolveProjectId(?string $value, int $companyId, ?int $clientId = null): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        $base = fn () => Project::where('company_id', $companyId);
        if (ctype_digit($value)) {
            $byId = $base()->where('id', (int) $value)->first();
            if ($byId) {
                return $byId->id;
            }
        }
        $project = $base()->where('name', $value)->first()
            ?? $base()->where('code', $value)->first()
            ?? $base()->where('name', 'like', "%{$value}%")->first()
            ?? $base()->where('code', 'like', "%{$value}%")->first();

        if (! $project) {
            $project = Project::create([
                'company_id' => $companyId,
                'client_id' => $clientId,
                'name' => $value,
                'code' => null,
                'status' => 'active',
            ]);
        }

        return $project->id;
    }

    private function isFormula(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return str_starts_with(trim($value), '=');
    }

    private function parseMonth(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

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

    /**
     * Only allow invoice statuses used by the app (see InvoicePage validation). Unknown values are skipped (default pending).
     */
    private function resolveImportedStatus(?string $raw): string
    {
        $allowed = ['pending', 'paid', 'partial', 'overdue', 'cancelled'];
        $t = trim((string) ($raw ?? ''));
        if ($t === '') {
            return 'pending';
        }
        $lower = mb_strtolower($t);
        foreach ($allowed as $a) {
            if ($a === $lower) {
                return $a;
            }
        }
        $synonym = $this->mapInvoiceStatusSynonym($lower);

        return ($synonym !== null && in_array($synonym, $allowed, true)) ? $synonym : 'pending';
    }

    private function mapInvoiceStatusSynonym(string $lower): ?string
    {
        $map = [
            'yes' => 'paid', 'si' => 'paid', 'sí' => 'paid',
            'pagado' => 'paid', 'pagada' => 'paid',
            'no' => 'pending', 'pendiente' => 'pending',
            'parcial' => 'partial',
            'vencido' => 'overdue', 'vencida' => 'overdue',
            'cancelado' => 'cancelled', 'cancelada' => 'cancelled',
        ];

        return $map[$lower] ?? null;
    }

    /**
     * Only allow payment types defined on Invoice::PAYMENT_TYPES. Unknown values are skipped (null).
     */
    private function resolveImportedPaymentType(?string $raw): ?string
    {
        $allowed = Invoice::PAYMENT_TYPES;
        $t = trim((string) ($raw ?? ''));
        if ($t === '') {
            return null;
        }
        $lower = mb_strtolower($t);
        foreach ($allowed as $a) {
            if ($a === $lower) {
                return $a;
            }
        }
        $synonym = [
            'transferencia' => 'transfer',
            'efectivo' => 'cash',
        ];
        $canonical = $synonym[$lower] ?? null;

        return ($canonical !== null && in_array($canonical, $allowed, true)) ? $canonical : null;
    }

    /**
     * Only accept bank names that exist on bank_accounts.bank_name (case-insensitive). Unknown values are skipped (null).
     */
    private function resolveImportedBankName(?string $raw, Collection $knownNames): ?string
    {
        $t = trim((string) ($raw ?? ''));
        if ($t === '') {
            return null;
        }
        $lower = mb_strtolower($t);
        foreach ($knownNames as $name) {
            if (mb_strtolower((string) $name) === $lower) {
                return (string) $name;
            }
        }

        return null;
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'm/d/Y', 'M-y', 'M-Y'] as $format) {
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

    private function parseAmount(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $raw = trim($value);
        $negative = $this->isNegativeAmount($raw);
        $value = str_replace([' ', '€', '$', "\xC2\xA0"], '', $raw);
        $value = preg_replace('/^[+\-]\s*/', '', $value);
        $value = preg_replace('/\s*[+\-]\s*$/', '', $value);
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

        if ($amount == 0.0) {
            return 0.0;
        }

        return $negative ? -abs($amount) : $amount;
    }

    private function isNegativeAmount(string $raw): bool
    {
        $normalized = str_replace([' ', '€', '$', "\xC2\xA0"], '', trim($raw));

        return preg_match('/^\-/', $normalized) === 1
            || preg_match('/\-\s*$/', $normalized) === 1
            || preg_match('/^\(.*\)$/', $normalized) === 1;
    }
}
