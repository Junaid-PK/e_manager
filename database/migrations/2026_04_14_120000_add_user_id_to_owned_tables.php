<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'companies',
        'clients',
        'projects',
        'invoices',
        'expenses',
        'bank_accounts',
        'bank_movements',
        'credit_lines',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            });
        }

        $adminUserId = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->orderBy('id')
            ->value('id');

        if (! $adminUserId) {
            $adminUserId = User::query()->orderBy('id')->value('id');
        }

        if ($adminUserId) {
            foreach ($this->tables as $table) {
                DB::table($table)->whereNull('user_id')->update(['user_id' => $adminUserId]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['user_id']);
                $blueprint->dropColumn('user_id');
            });
        }
    }
};
