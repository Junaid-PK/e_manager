<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('company_id')
                ->constrained('clients')
                ->nullOnDelete();
        });

        DB::table('project_months')
            ->orderByDesc('id')
            ->get(['project_id', 'client_id'])
            ->unique('project_id')
            ->each(function ($projectMonth) {
                DB::table('projects')
                    ->where('id', $projectMonth->project_id)
                    ->update(['client_id' => $projectMonth->client_id]);
            });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
