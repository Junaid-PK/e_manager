<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('invoice_number');
            $table->string('month')->nullable();
            $table->date('date_issued');
            $table->date('date_due')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('iva_amount', 12, 2)->default(0);
            $table->decimal('iva_rate', 5, 2)->default(21);
            $table->decimal('retention_amount', 12, 2)->default(0);
            $table->decimal('retention_rate', 5, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('company_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
