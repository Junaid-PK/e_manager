<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $types = [
            ['name' => 'Transfer', 'slug' => 'transfer', 'sort_order' => 1],
            ['name' => 'Commission', 'slug' => 'commission', 'sort_order' => 2],
            ['name' => 'Card Payment', 'slug' => 'card_payment', 'sort_order' => 3],
            ['name' => 'Direct Debit', 'slug' => 'direct_debit', 'sort_order' => 4],
            ['name' => 'Other', 'slug' => 'other', 'sort_order' => 5],
        ];

        foreach ($types as $type) {
            DB::table('movement_types')->insertOrIgnore(array_merge($type, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('movement_types')->whereIn('slug', [
            'transfer', 'commission', 'card_payment', 'direct_debit', 'other',
        ])->delete();
    }
};
