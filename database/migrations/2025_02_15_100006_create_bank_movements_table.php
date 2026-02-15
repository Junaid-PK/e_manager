<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('date');
            $table->date('value_date')->nullable();
            $table->string('type')->default('other');
            $table->text('concept');
            $table->string('beneficiary')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('deposit', 12, 2)->nullable();
            $table->decimal('withdrawal', 12, 2)->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->string('import_source')->default('manual');
            $table->timestamps();

            $table->index('bank_account_id');
            $table->index('date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_movements');
    }
};
