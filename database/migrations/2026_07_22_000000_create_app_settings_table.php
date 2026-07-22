<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value');
        });

        DB::table('app_settings')->insert([
            ['key' => 'peon_social_security_rate', 'value' => '25'],
            ['key' => 'expert_social_security_rate', 'value' => '25'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
