<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('remindable_type');
            $table->unsignedBigInteger('remindable_id');
            $table->date('reminder_date');
            $table->text('message')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();

            $table->index(['remindable_type', 'remindable_id']);
            $table->index('reminder_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
