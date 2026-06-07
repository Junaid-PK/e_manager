<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('worker_import_entries', function (Blueprint $table) {
            $table->string('status_at_import')->default('new')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_import_entries', function (Blueprint $table) {
            $table->enum('status_at_import', ['new', 'active', 'removed'])->default('new')->change();
        });
    }
};
