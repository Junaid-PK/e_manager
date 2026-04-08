<?php

use App\Models\BankMovement;
use App\Models\Expense;
use App\Models\ExpenseCif;
use App\Models\ExpenseProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('expense_cifs', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code');
        });

        $pOrder = 0;
        foreach (Expense::query()->whereNotNull('vendor')->distinct()->pluck('vendor') as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            ExpenseProvider::firstOrCreate(
                ['name' => $name],
                ['sort_order' => ++$pOrder]
            );
        }

        foreach (BankMovement::query()->whereIn('type', ['buy', 'compra'])->whereNotNull('beneficiary')->distinct()->pluck('beneficiary') as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            ExpenseProvider::firstOrCreate(
                ['name' => $name],
                ['sort_order' => ++$pOrder]
            );
        }

        $cOrder = 0;
        foreach (Expense::query()->whereNotNull('listado_extra')->cursor() as $expense) {
            $extra = $expense->listado_extra;
            if (! is_array($extra) || empty($extra['cif'])) {
                continue;
            }
            $code = trim((string) $extra['cif']);
            if ($code === '') {
                continue;
            }
            ExpenseCif::firstOrCreate(
                ['code' => $code],
                ['sort_order' => ++$cOrder]
            );
        }

        foreach (BankMovement::query()->whereNotNull('listado_extra')->cursor() as $m) {
            $extra = $m->listado_extra;
            if (! is_array($extra) || empty($extra['cif'])) {
                continue;
            }
            $code = trim((string) $extra['cif']);
            if ($code === '') {
                continue;
            }
            ExpenseCif::firstOrCreate(
                ['code' => $code],
                ['sort_order' => ++$cOrder]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_cifs');
        Schema::dropIfExists('expense_providers');
    }
};
