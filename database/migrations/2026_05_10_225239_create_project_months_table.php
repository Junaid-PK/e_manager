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
        Schema::create('project_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_period_id')->constrained('monthly_periods')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('sheet_code')->nullable();
            $table->decimal('total_nominal', 12, 2)->default(0);
            $table->decimal('total_social_security', 12, 2)->default(0);
            $table->decimal('total_expenses', 12, 2)->default(0);
            $table->decimal('total_invoiced', 12, 2)->default(0);
            $table->decimal('estimated_invoice', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_months');
    }
};
