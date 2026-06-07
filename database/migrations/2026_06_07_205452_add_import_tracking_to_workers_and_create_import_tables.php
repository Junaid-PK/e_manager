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
        // Add import tracking columns to workers table
        Schema::table('workers', function (Blueprint $table) {
            $table->enum('import_status', ['new', 'active', 'removed'])->default('active')->after('bank_account');
            $table->timestamp('first_imported_at')->nullable()->after('import_status');
            $table->timestamp('last_imported_at')->nullable()->after('first_imported_at');
        });

        // Create worker_imports table to track each import event
        Schema::create('worker_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('new_count')->default(0);
            $table->integer('active_count')->default(0);
            $table->integer('removed_count')->default(0);
            $table->timestamps();
        });

        // Create worker_import_entries table to track which workers were in each import
        Schema::create('worker_import_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_import_id')->constrained('worker_imports')->onDelete('cascade');
            $table->foreignId('worker_id')->nullable()->constrained('workers')->onDelete('set null');
            $table->string('full_name');
            $table->string('nie')->nullable();
            $table->string('bank_account')->nullable();
            $table->enum('status_at_import', ['new', 'active', 'removed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_import_entries');
        Schema::dropIfExists('worker_imports');

        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn(['import_status', 'first_imported_at', 'last_imported_at']);
        });
    }
};
