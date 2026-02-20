<?php

namespace Database\Factories;

use App\Models\CategoryDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategoryDiscount>
 */
class CategoryDiscountFactory extends Factory
{
    protected $model = CategoryDiscount::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discount_percentage' => $this->faker->randomFloat(2, 0, 20),
            'is_active' => true,
        ];
    }

     public function forCategory(int $categoryId, float $percentage): self
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
            'discount_percentage' => $percentage,
        ]);
    }
}
