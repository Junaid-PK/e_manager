<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@emanager.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $mon2025 = Company::create([
            'name' => 'MON2025',
            'tax_id' => 'B12345678',
            'email' => 'info@mon2025.com',
        ]);

        $clients = [
            ['name' => 'Vesta Rehabilitacion S.L', 'tax_id' => 'B11111111'],
            ['name' => 'Acciona Construcción, S.A.', 'tax_id' => 'A22222222'],
            ['name' => 'Gestion Ingenieria y Construccion de la Costa', 'tax_id' => 'B33333333'],
            ['name' => 'Construccions Ferre S.L.', 'tax_id' => 'B44444444'],
            ['name' => 'Ecopintura 2023 S.L.', 'tax_id' => 'B55555555'],
            ['name' => 'Garcia Riera SL', 'tax_id' => 'B66666666'],
            ['name' => 'Recop Restauracions Arquitectoniques', 'tax_id' => 'B77777777'],
            ['name' => 'Constructora Del Cardoner, S.A.', 'tax_id' => 'A88888888'],
            ['name' => 'Heimsun Power SL', 'tax_id' => 'B99999999'],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }

        $banks = [
            ['bank_name' => 'Banco Sabadell', 'account_number' => 'ES12 0081 0087 5700 0175 8181', 'holder_name' => 'Brother Taxi Transport S.L.', 'currency' => 'EUR', 'initial_balance' => 68451.89, 'current_balance' => 74307.21],
            ['bank_name' => 'Banco Santander', 'account_number' => 'ES98 0049 1234 5678 9012 3456', 'holder_name' => 'MON2025', 'currency' => 'EUR', 'initial_balance' => 28011.88, 'current_balance' => 28011.88],
            ['bank_name' => 'La Caixa', 'account_number' => 'ES45 2100 9876 5432 1098 7654', 'holder_name' => 'MON2025', 'currency' => 'EUR', 'initial_balance' => 0, 'current_balance' => 0],
            ['bank_name' => 'BBVA', 'account_number' => 'ES67 0182 1111 2222 3333 4444', 'holder_name' => 'MON2025', 'currency' => 'EUR', 'initial_balance' => 1440.80, 'current_balance' => 1440.80],
        ];

        foreach ($banks as $bank) {
            BankAccount::create($bank);
        }
    }
}
