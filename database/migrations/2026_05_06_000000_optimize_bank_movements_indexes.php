<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_movements', function (Blueprint $table) {
            $table->index(['bank_account_id', 'date', 'id'], 'bank_movements_account_date_id_idx');
            $table->index(['user_id', 'date', 'id'], 'bank_movements_user_date_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bank_movements', function (Blueprint $table) {
            $table->dropIndex('bank_movements_account_date_id_idx');
            $table->dropIndex('bank_movements_user_date_id_idx');
        });
    }
};
