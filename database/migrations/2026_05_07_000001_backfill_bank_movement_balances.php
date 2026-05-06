<?php

use App\Models\BankAccount;
use App\Services\BankMovementBalanceService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(BankMovementBalanceService::class);

        BankAccount::query()
            ->select('id')
            ->orderBy('id')
            ->pluck('id')
            ->each(fn (int $accountId) => $service->recalculateAccount($accountId));
    }

    public function down(): void
    {
        //
    }
};
