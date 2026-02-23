<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_type')->nullable()->after('status');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('total');
            $table->decimal('amount_remaining', 12, 2)->default(0)->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'amount_paid', 'amount_remaining']);
        });
    }
};
