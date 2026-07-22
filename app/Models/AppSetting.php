<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function socialSecurityRate(string $workerRole): float
    {
        $key = $workerRole === 'expert'
            ? 'expert_social_security_rate'
            : 'peon_social_security_rate';

        return (float) (static::query()
            ->whereKey($key)
            ->value('value') ?? 25);
    }

    public static function setSocialSecurityRates(float $peonRate, float $expertRate): void
    {
        foreach (['peon' => $peonRate, 'expert' => $expertRate] as $role => $rate) {
            static::query()->updateOrCreate(
                ['key' => $role.'_social_security_rate'],
                ['value' => (string) $rate]
            );
        }
    }
}
