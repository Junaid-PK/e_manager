<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\MovementCategory;
use App\Models\MovementType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedMovementsPerformanceData extends Command
{
    protected $signature = 'app:seed-movements-performance-data {count=3000 : Number of movements to add}';

    protected $description = 'Seed extra bank movements for performance testing';

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $userId = User::query()->value('id');
        if (! $userId) {
            $this->error('No users found');

            return self::FAILURE;
        }

        $accountIds = BankAccount::query()->pluck('id')->toArray();
        if ($accountIds === []) {
            $this->error('No bank accounts found');

            return self::FAILURE;
        }

        $types = MovementType::query()->pluck('slug')->toArray();
        if ($types === []) {
            $types = ['transfer', 'deposit', 'withdrawal', 'bill'];
        }

        $categories = MovementCategory::query()->pluck('name')->toArray();

        $concepts = [
            'Pago proveedor', 'Ingreso cliente', 'Transferencia interna', 'Compra material',
            'Pago nómina', 'Recibo luz', 'Recibo agua', 'Alquiler', 'Seguro', 'Gasolina',
            'Transporte', 'Mantenimiento', 'Licencia software', 'Publicidad', 'Viaje',
            'Tasas bancarias', 'Devolución', 'Anticipo', 'Cobro factura', 'Otros gastos',
        ];

        $beneficiaries = [
            'Proveedores SL', 'Cliente ABC', 'Luz y Gas SA', 'Aseguradora Global',
            'Banco Sabadell', 'Alquileres Local', 'Estación Servicio', 'Telefónica',
            'Material Obra SL', 'Empleados', 'Hacienda', 'Seguridad Social',
        ];

        $this->info("Seeding {$count} bank movements...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $chunkSize = 500;
        $now = now();
        $inserts = [];

        for ($i = 0; $i < $count; $i++) {
            $type = $types[array_rand($types)];
            $isDeposit = rand(1, 100) > 55;
            $amount = round(rand(10, 5000) + rand(0, 99) / 100, 2);
            $date = now()->subDays(rand(0, 730))->format('Y-m-d');

            $inserts[] = [
                'user_id' => $userId,
                'bank_account_id' => $accountIds[array_rand($accountIds)],
                'date' => $date,
                'value_date' => rand(0, 10) > 7 ? now()->subDays(rand(0, 730))->format('Y-m-d') : null,
                'type' => $type,
                'concept' => $concepts[array_rand($concepts)].' '.rand(1000, 9999),
                'beneficiary' => $beneficiaries[array_rand($beneficiaries)],
                'reference' => 'REF-'.str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'deposit' => $isDeposit ? $amount : null,
                'withdrawal' => $isDeposit ? null : $amount,
                'balance' => 0,
                'category' => $type !== 'bill' && $categories !== [] ? $categories[array_rand($categories)] : null,
                'notes' => rand(0, 10) > 8 ? 'Nota de prueba '.$i : null,
                'import_source' => 'performance_seed',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($inserts) >= $chunkSize) {
                DB::table('bank_movements')->insert($inserts);
                $inserts = [];
                $bar->advance($chunkSize);
            }
        }

        if ($inserts !== []) {
            DB::table('bank_movements')->insert($inserts);
            $bar->advance(count($inserts));
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done. Total movements: '.BankMovement::count());

        return self::SUCCESS;
    }
}
