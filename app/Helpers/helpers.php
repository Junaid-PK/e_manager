<?php

if (! function_exists('fmt_money')) {
    function fmt_money(float|string|null $amount, string $currency = '€'): string
    {
        $value = (float) ($amount ?? 0);
        return number_format($value, 2, '.', ',') . ($currency ? " {$currency}" : '');
    }
}

if (! function_exists('fmt_number')) {
    function fmt_number(float|string|null $amount, int $decimals = 2): string
    {
        $value = (float) ($amount ?? 0);
        return number_format($value, $decimals, '.', ',');
    }
}
