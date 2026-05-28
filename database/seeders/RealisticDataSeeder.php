<?php

namespace Database\Seeders;

use App\Models\Worker;
use App\Models\ProjectMonth;
use App\Models\ProjectInvoice;
use App\Models\ProjectExpense;
use App\Models\WorkerMonthlySummary;
use App\Models\WorkerPayment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RealisticDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedWorkers();
        $this->seedProjectMonths();
        $this->seedWorkerProjectEntries();
        $this->seedProjectInvoices();
        $this->seedProjectExpenses();
        $this->seedWorkerPayments();
    }

    private function seedWorkers(): void
    {
        $workers = [
            ['full_name' => 'Carlos Rodríguez Martínez', 'nie' => 'X1234567A', 'bank_account' => 'ES91 2100 0418 4502 0005 1332'],
            ['full_name' => 'María García López', 'nie' => 'Y2345678B', 'bank_account' => 'ES79 0049 1500 0123 4567 8901'],
            ['full_name' => 'José Luis Fernández Sánchez', 'nie' => 'Z3456789C', 'bank_account' => 'ES66 0182 1111 2222 3333 4444'],
            ['full_name' => 'Ana Martínez Pérez', 'nie' => 'X4567890D', 'bank_account' => 'ES44 2038 0001 7777 8888 9999'],
            ['full_name' => 'Francisco Javier Ruiz Gómez', 'nie' => 'Y5678901E', 'bank_account' => 'ES33 0073 0100 5555 6666 7777'],
            ['full_name' => 'Laura Sánchez Torres', 'nie' => 'Z6789012F', 'bank_account' => 'ES22 0081 1234 5678 9012 3456'],
            ['full_name' => 'Antonio Moreno Jiménez', 'nie' => 'X7890123G', 'bank_account' => 'ES11 1491 0001 3333 4444 5555'],
            ['full_name' => 'Carmen López Hernández', 'nie' => 'Y8901234H', 'bank_account' => 'ES00 2095 0002 1111 2222 3333'],
            ['full_name' => 'Manuel Gómez Díaz', 'nie' => 'Z9012345I', 'bank_account' => 'ES88 3025 0003 9999 0000 1111'],
            ['full_name' => 'Isabel Pérez Álvarez', 'nie' => 'X0123456J', 'bank_account' => 'ES77 0049 1234 5678 9012 3456'],
            ['full_name' => 'David Romero Castro', 'nie' => 'Y1234567K', 'bank_account' => 'ES55 2100 9876 5432 1098 7654'],
            ['full_name' => 'Patricia Navarro Molina', 'nie' => 'Z2345678L', 'bank_account' => 'ES44 0075 0001 2222 3333 4444'],
            ['full_name' => 'Javier Ortiz Delgado', 'nie' => 'X3456789M', 'bank_account' => 'ES33 0182 5555 6666 7777 8888'],
            ['full_name' => 'Elena Vargas Guerrero', 'nie' => 'Y4567890N', 'bank_account' => 'ES22 2100 3333 4444 5555 6666'],
            ['full_name' => 'Miguel Ángel Herrera Santos', 'nie' => 'Z5678901O', 'bank_account' => 'ES11 0049 7777 8888 9999 0000'],
            ['full_name' => 'Sofía Reyes Cruz', 'nie' => 'X6789012P', 'bank_account' => 'ES99 2038 4444 5555 6666 7777'],
            ['full_name' => 'Daniel Morales Flores', 'nie' => 'Y7890123Q', 'bank_account' => 'ES88 0081 6666 7777 8888 9999'],
            ['full_name' => 'Lucía Ramos Aguilar', 'nie' => 'Z8901234R', 'bank_account' => 'ES77 1491 8888 9999 0000 1111'],
            ['full_name' => 'Alejandro Castillo Vargas', 'nie' => 'X9012345S', 'bank_account' => 'ES66 2095 0001 2345 6789 0123'],
            ['full_name' => 'Paula Domínguez Campos', 'nie' => 'Y0123456T', 'bank_account' => 'ES55 3025 0002 3456 7890 1234'],
        ];

        foreach ($workers as $worker) {
            Worker::create($worker);
        }
    }

    private function seedProjectMonths(): void
    {
        $clientIds = DB::table('clients')->pluck('id')->toArray();
        $projectIds = DB::table('projects')->pluck('id')->toArray();
        $periodIds = DB::table('monthly_periods')->pluck('id')->toArray();

        $sheetPrefixes = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'M', 'P'];

        for ($i = 0; $i < 30; $i++) {
            $totalExpenses = round(rand(1000, 15000) + rand(0, 99) / 100, 2);
            $estimatedInvoice = round(rand(15000, 80000) + rand(0, 99) / 100, 2);
            $totalInvoiced = round($estimatedInvoice * (rand(70, 120) / 100), 2);
            $difference = round($estimatedInvoice - $totalInvoiced, 2);

            ProjectMonth::create([
                'monthly_period_id' => $periodIds[array_rand($periodIds)],
                'client_id' => $clientIds[array_rand($clientIds)],
                'project_id' => $projectIds[array_rand($projectIds)],
                'sheet_code' => $sheetPrefixes[array_rand($sheetPrefixes)] . str_pad(rand(100, 999), 4, '0', STR_PAD_LEFT),
                'total_nominal' => 0,
                'total_social_security' => 0,
                'total_expenses' => $totalExpenses,
                'total_invoiced' => $totalInvoiced,
                'estimated_invoice' => $estimatedInvoice,
                'difference' => $difference,
                'total_hours' => 0,
            ]);
        }
    }

    private function seedWorkerProjectEntries(): void
    {
        $workerIds = DB::table('workers')->pluck('id')->toArray();
        $projectMonthIds = DB::table('project_months')->pluck('id')->toArray();
        $notes = [
            null, 'Baja por enfermedad', 'Vacaciones', 'Festivo', 'Horas extras',
            'Día completo', 'Medio día', 'Incapacidad temporal', null, null
        ];

        foreach ($projectMonthIds as $pmId) {
            $numWorkers = rand(3, 12);
            for ($i = 0; $i < $numWorkers; $i++) {
                $hours = round(rand(80, 200) + rand(0, 99) / 100, 2);
                $days = round($hours / 8, 2);
                $rate = round(rand(12, 25) + rand(0, 99) / 100, 2);
                $ss = round($hours * $rate * 0.15 + rand(0, 50), 2);
                $total = $ss + ($hours * $rate);
                $paid = round($total * (rand(0, 100) / 100), 2);

                \App\Models\WorkerProjectEntry::create([
                    'project_month_id' => $pmId,
                    'worker_id' => $workerIds[array_rand($workerIds)],
                    'special_note' => $notes[array_rand($notes)],
                    'social_security' => $ss,
                    'hours' => $hours,
                    'days' => $days,
                    'rate' => $rate,
                    'paid_amount' => $paid,
                ]);
            }
        }
    }

    private function seedProjectInvoices(): void
    {
        $projectMonthIds = DB::table('project_months')->pluck('id')->toArray();
        $statuses = ['draft', 'sent', 'paid', 'partial', 'cancelled'];
        $statusWeights = ['draft' => 15, 'sent' => 25, 'paid' => 40, 'partial' => 15, 'cancelled' => 5];

        for ($i = 0; $i < 40; $i++) {
            $projectMonthId = $projectMonthIds[array_rand($projectMonthIds)];
            $projectMonth = DB::table('project_months')->find($projectMonthId);
            $estimatedAmount = round(rand(2000, 30000) + rand(0, 99) / 100, 2);
            $actualAmount = round($estimatedAmount * (rand(0, 120) / 100), 2);
            $status = $this->weightedRandom($statusWeights);

            ProjectInvoice::create([
                'project_month_id' => $projectMonthId,
                'invoice_no' => 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'invoice_date' => now()->subDays(rand(0, 365))->format('Y-m-d'),
                'estimated_amount' => $estimatedAmount,
                'actual_amount' => $actualAmount,
                'status' => $status,
                'notes' => $this->randomInvoiceNote(),
            ]);
        }
    }

    private function seedProjectExpenses(): void
    {
        $projectMonthIds = DB::table('project_months')->pluck('id')->toArray();
        $categories = [
            'Material', 'Transporte', 'Herramienta', 'Seguridad', 'Alquiler',
            'Comida', 'Gasolina', 'Teléfono', 'Luz', 'Agua', 'Otros'
        ];

        $descriptions = [
            'Compra de cemento y arena para obra',
            'Transporte de materiales a sitio',
            'Alquiler de andamios mensual',
            'EPIS para trabajadores',
            'Reparación de herramienta',
            'Material de fontanería',
            'Viaje a obra Barcelona',
            'Combustible furgoneta',
            'Comida equipo trabajo',
            'Material electricidad',
            'Pintura y disolventes',
            'Limpieza final obra',
            'Peaje autopista',
            'Material pladur',
            'Varilla y hierro',
        ];

        for ($i = 0; $i < 50; $i++) {
            ProjectExpense::create([
                'project_month_id' => $projectMonthIds[array_rand($projectMonthIds)],
                'expense_date' => now()->subDays(rand(0, 365))->format('Y-m-d'),
                'category' => $categories[array_rand($categories)],
                'description' => $descriptions[array_rand($descriptions)],
                'amount' => round(rand(50, 5000) + rand(0, 99) / 100, 2),
            ]);
        }
    }

    private function seedWorkerPayments(): void
    {
        $workerIds = DB::table('workers')->pluck('id')->toArray();
        $periodIds = DB::table('monthly_periods')->pluck('id')->toArray();
        $projectMonthIds = DB::table('project_months')->pluck('id')->toArray();
        $paymentTypes = ['bank', 'cash', 'advance', 'ticket', 'adjustment'];
        $typeWeights = ['bank' => 45, 'cash' => 25, 'advance' => 15, 'ticket' => 10, 'adjustment' => 5];
        $references = [
            'Transferencia bancaria', 'Pago en mano', 'Adelanto semanal', 'Vale comida',
            'Ajuste nómina', 'Pago extra', 'Bonificación', 'Pago parcial',
            'Liquidación final', 'Pago mensualidad', 'Transferencia Sabadell',
            'Transferencia Santander', 'Caja', 'Cheque', 'Bizum'
        ];

        for ($i = 0; $i < 60; $i++) {
            WorkerPayment::create([
                'worker_id' => $workerIds[array_rand($workerIds)],
                'monthly_period_id' => $periodIds[array_rand($periodIds)],
                'project_month_id' => $projectMonthIds[array_rand($projectMonthIds)],
                'payment_date' => now()->subDays(rand(0, 365))->format('Y-m-d'),
                'payment_type' => $this->weightedRandom($typeWeights),
                'amount' => round(rand(100, 3000) + rand(0, 99) / 100, 2),
                'reference' => $references[array_rand($references)] . ' ' . rand(100, 999),
                'notes' => $this->randomPaymentNote(),
            ]);
        }
    }

    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        $current = 0;
        foreach ($weights as $key => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $key;
            }
        }
        return array_key_first($weights);
    }

    private function randomInvoiceNote(): ?string
    {
        $notes = [
            null,
            'Factura pendiente de pago',
            'Pagada por transferencia',
            'Pago aplazado según acuerdo',
            'Factura rectificada',
            'Enviada por email',
            'Pendiente de confirmación',
            'Abonada parcialmente',
            'Pago recibido con retraso',
            'En disputa con cliente',
            null,
        ];
        return $notes[array_rand($notes)];
    }

    private function randomPaymentNote(): ?string
    {
        $notes = [
            null,
            'Pago regular mensual',
            'Adelanto solicitado por trabajador',
            'Pago de horas extras',
            'Ajuste por error anterior',
            'Pago de finiquito',
            'Prima de productividad',
            'Pago de vacaciones',
            'Anticipo de nómina',
            'Liquidación de ticket restaurante',
            null,
            'Pago completado correctamente',
        ];
        return $notes[array_rand($notes)];
    }
}
