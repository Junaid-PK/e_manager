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
        // worker_id BIGINT UNSIGNED NOT NULL,
        // monthly_period_id BIGINT UNSIGNED NULL,
        // project_month_id BIGINT UNSIGNED NULL,

        // payment_date DATE NOT NULL,
        // payment_type ENUM('bank', 'cash', 'advance', 'ticket', 'adjustment') NOT NULL,
        // amount DECIMAL(12,2) NOT NULL,
        // reference VARCHAR(255) NULL,
        // notes TEXT NULL,
        Schema::create('worker_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->foreignId('monthly_period_id')->constrained('monthly_periods')->cascadeOnDelete();
            $table->foreignId('project_month_id')->constrained('project_months')->cascadeOnDelete();
            $table->date('payment_date');
            $table->enum('payment_type', ['bank', 'cash', 'advance', 'ticket', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_payments');
    }
};
