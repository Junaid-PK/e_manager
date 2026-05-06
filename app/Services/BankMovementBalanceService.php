<?php

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

class BankMovementBalanceService
{
    /**
     * @param  array<int>  $accountIds
     */
    public function recalculateAccounts(array $accountIds): void
    {
        collect($accountIds)
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->each(fn (int $accountId) => $this->recalculateAccount($accountId));
    }

    public function recalculateAccount(int $accountId): void
    {
        $account = BankAccount::query()->find($accountId);

        if (! $account) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement(
                <<<'SQL'
                UPDATE bank_movements
                SET balance = (
                    SELECT ROUND(
                        ? + COALESCE(SUM(COALESCE(bm2.deposit, 0) - COALESCE(bm2.withdrawal, 0)), 0),
                        2
                    )
                    FROM bank_movements bm2
                    WHERE bm2.bank_account_id = bank_movements.bank_account_id
                      AND (
                          bm2.date < bank_movements.date
                          OR (bm2.date = bank_movements.date AND bm2.id <= bank_movements.id)
                      )
                )
                WHERE bank_account_id = ?
                SQL,
                [(float) $account->initial_balance, $accountId]
            );
        } else {
            DB::statement(
                <<<'SQL'
                UPDATE bank_movements bm
                JOIN (
                    SELECT
                        m.id,
                        ROUND(
                            ? + SUM(COALESCE(m.deposit, 0) - COALESCE(m.withdrawal, 0))
                            OVER (PARTITION BY m.bank_account_id ORDER BY m.date, m.id),
                            2
                        ) AS running_balance
                    FROM bank_movements m
                    WHERE m.bank_account_id = ?
                ) balances ON balances.id = bm.id
                SET bm.balance = balances.running_balance
                WHERE bm.bank_account_id = ?
                SQL,
                [(float) $account->initial_balance, $accountId, $accountId]
            );
        }

        $latestBalance = DB::table('bank_movements')
            ->where('bank_account_id', $accountId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->value('balance');

        $account->update([
            'current_balance' => $latestBalance !== null
                ? round((float) $latestBalance, 2)
                : round((float) $account->initial_balance, 2),
        ]);
    }
}
