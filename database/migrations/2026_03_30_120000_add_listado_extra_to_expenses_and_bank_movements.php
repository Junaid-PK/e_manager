<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->json('listado_extra')->nullable()->after('notes');
        });

        Schema::table('bank_movements', function (Blueprint $table) {
            $table->json('listado_extra')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('listado_extra');
        });

        Schema::table('bank_movements', function (Blueprint $table) {
            $table->dropColumn('listado_extra');
        });
    }
};
