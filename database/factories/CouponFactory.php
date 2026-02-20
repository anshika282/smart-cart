<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    { $type = $this->faker->randomElement(['fixed', 'percent']);
        $value = ($type === 'fixed') ? $this->faker->randomFloat(2, 50, 500) : $this->faker->randomFloat(2, 5, 25);
        $maxDiscount = ($type === 'percent') ? $this->faker->randomElement([null, $this->faker->randomFloat(2, 200, 1000)]) : null;

        return [
            'code' => $this->faker->unique()->word() . $this->faker->randomNumber(3, true),
            'type' => $type,
            'value' => $value,
            'max_discount_amount' => $maxDiscount,
            'min_cart_amount' => $this->faker->randomFloat(2, 0, 1500),
            'starts_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+6 months'),
            'usage_limit' => $this->faker->randomElement([null, $this->faker->numberBetween(10, 500)]),
            'used_count' => 0,
        ];
    }

     public function fixed(float $value): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'value' => $value,
            'max_discount_amount' => null,
        ]);
    }

    public function percent(float $value, ?float $maxDiscount = null): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percent',
            'value' => $value,
            'max_discount_amount' => $maxDiscount,
        ]);
    }

    public function newuser(): self
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'NEWUSER',
            'type' => 'fixed',
            'value' => 200.00,
            'max_discount_amount' => null,
            'min_cart_amount' => 0.00,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addYear(),
            'usage_limit' => null,
            'used_count' => 0,
        ]);
    }

    public function festive(): self
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'FESTIVE',
            'type' => 'percent',
            'value' => 15.00, // For 15%
            'max_discount_amount' => 500.00,
            'min_cart_amount' => 0.00,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonth(),
            'usage_limit' => null,
            'used_count' => 0,
        ]);
    }
}
