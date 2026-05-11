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
        // project_month_id BIGINT UNSIGNED NOT NULL,
        // invoice_no VARCHAR(100) NULL,
        // invoice_date DATE NULL,
        // estimated_amount DECIMAL(12,2) DEFAULT 0,
        // actual_amount DECIMAL(12,2) DEFAULT 0,
        // status ENUM('draft', 'sent', 'paid', 'partial', 'cancelled') DEFAULT 'draft',
        // notes TEXT NULL,
        Schema::create('project_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_month_id')->constrained('project_months')->cascadeOnDelete();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('estimated_amount', 12, 2)->default(0);
            $table->decimal('actual_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'sent', 'paid', 'partial', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_invoices');
    }
};
