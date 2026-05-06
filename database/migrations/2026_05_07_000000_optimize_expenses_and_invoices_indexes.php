<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['user_id', 'date', 'id'], 'expenses_user_date_id_idx');
            $table->index(['user_id', 'company_id', 'date'], 'expenses_user_company_date_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['user_id', 'date_issued', 'id'], 'invoices_user_issued_id_idx');
            $table->index(['user_id', 'status', 'date_issued'], 'invoices_user_status_issued_idx');
            $table->index(['user_id', 'company_id', 'date_issued'], 'invoices_user_company_issued_idx');
            $table->index(['user_id', 'client_id', 'date_issued'], 'invoices_user_client_issued_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_user_issued_id_idx');
            $table->dropIndex('invoices_user_status_issued_idx');
            $table->dropIndex('invoices_user_company_issued_idx');
            $table->dropIndex('invoices_user_client_issued_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_user_date_id_idx');
            $table->dropIndex('expenses_user_company_date_idx');
        });
    }
};
