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
        Schema::create('worker_project_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_month_id')->constrained('project_months')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->text('special_note')->nullable();
            $table->decimal('social_security', 12, 2)->default(0);
            $table->decimal('hours', 10, 2)->default(0);
            $table->decimal('days', 10, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_project_entries');
    }
};
