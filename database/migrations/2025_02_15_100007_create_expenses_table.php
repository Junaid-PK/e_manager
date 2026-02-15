<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('category')->nullable();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('vendor')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('receipt_path')->nullable();
            $table->boolean('recurring')->default(false);
            $table->string('recurring_frequency')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('category');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
