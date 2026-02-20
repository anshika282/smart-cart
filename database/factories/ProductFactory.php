<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(), // Automatically creates a category if none exist or uses a new one
            'name' => $this->faker->unique()->words(rand(1, 3), true),
            'sku' => Str::upper(Str::random(3)) . '-' . $this->faker->randomNumber(4, true), // e.g., ABC-1234
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 100, 5000), // Price between 100 and 5000
            'stock_quantity' => $this->faker->numberBetween(0, 1000), // Stock between 0 and 1000
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

     public function outOfStock(): self
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
