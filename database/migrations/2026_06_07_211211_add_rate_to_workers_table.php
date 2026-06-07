<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->decimal('rate', 10, 2)->default(0)->after('bank_account');
        });

        // Migrate existing rates from worker_project_entries to workers
        // Use the most recent entry's rate for each worker
        $workerRates = DB::table('worker_project_entries')
            ->select('worker_id', DB::raw('MAX(rate) as rate'))
            ->where('rate', '>', 0)
            ->groupBy('worker_id')
            ->get();

        foreach ($workerRates as $workerRate) {
            DB::table('workers')
                ->where('id', $workerRate->worker_id)
                ->update(['rate' => $workerRate->rate]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn('rate');
        });
    }
};
