<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = \App\Models\Invoice::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100, 10000);
        $ivaRate = fake()->randomElement([0, 10, 21]);
        $ivaAmount = round($amount * $ivaRate / 100, 2);
        $total = round($amount + $ivaAmount, 2);

        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'invoice_number' => fake()->unique()->numberBetween(1, 99999),
            'date_issued' => fake()->dateTimeBetween('-1 year', 'now'),
            'date_due' => fake()->optional()->dateTimeBetween('now', '+60 days'),
            'amount' => $amount,
            'iva_rate' => $ivaRate,
            'iva_amount' => $ivaAmount,
            'retention_rate' => 0,
            'retention_amount' => 0,
            'total' => $total,
            'amount_paid' => 0,
            'amount_remaining' => $total,
            'status' => 'pending',
            'paid_date' => null,
            'retention_paid_date' => null,
            'bank_date' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function partiallyPaid(?float $paidAmount = null): static
    {
        return $this->state(function (array $attributes) use ($paidAmount) {
            $total = (float) $attributes['total'];
            $paid = $paidAmount ?? round($total * fake()->randomFloat(2, 0.1, 0.9), 2);

            return [
                'amount_paid' => $paid,
                'amount_remaining' => max(0, round($total - $paid, 2)),
                'status' => 'partial',
                'paid_date' => fake()->dateTimeBetween('-6 months', 'now'),
            ];
        });
    }

    public function fullyPaid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'amount_paid' => $attributes['total'],
                'amount_remaining' => 0,
                'status' => 'paid',
                'paid_date' => fake()->dateTimeBetween('-6 months', 'now'),
            ];
        });
    }
}
