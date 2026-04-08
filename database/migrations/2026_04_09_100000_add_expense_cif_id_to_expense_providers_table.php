<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_providers', function (Blueprint $table) {
            $table->foreignId('expense_cif_id')
                ->nullable()
                ->after('sort_order')
                ->constrained('expense_cifs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expense_providers', function (Blueprint $table) {
            $table->dropForeign(['expense_cif_id']);
        });
    }
};
