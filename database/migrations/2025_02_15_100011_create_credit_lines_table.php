<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_lines', function (Blueprint $table) {
            $table->id();
            $table->string('entity_name');
            $table->string('entity_type')->default('bank');
            $table->integer('year')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_remaining', 12, 2)->default(0);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('entity_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_lines');
    }
};
