<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clothingCategory = Category::where('name', 'Clothing')->first();
        $footwearCategory = Category::where('name', 'Footwear')->first();
        $electronicsCategory = Category::where('name', 'Electronics')->first();
        $booksCategory = Category::where('name', 'Books')->first();
        $groceriesCategory = Category::where('name', 'Groceries')->first();


        // Seed 10 clothing products
        if ($clothingCategory) {
            Product::factory()->count(10)->create(['category_id' => $clothingCategory->id]);
        }

        // Seed 8 footwear products
        if ($footwearCategory) {
            Product::factory()->count(8)->create(['category_id' => $footwearCategory->id]);
        }

        // Seed 5 electronics products
        if ($electronicsCategory) {
            Product::factory()->count(5)->create(['category_id' => $electronicsCategory->id]);
        }

        // Seed 15 books products (if category exists)
        if ($booksCategory) {
            Product::factory()->count(15)->create(['category_id' => $booksCategory->id]);
        }

        // Seed 20 groceries products (if category exists)
        if ($groceriesCategory) {
            Product::factory()->count(20)->create(['category_id' => $groceriesCategory->id]);
        }

        // Seed a few special products for edge cases
        Product::factory()->count(2)->outOfStock()->create([
            'category_id' => $clothingCategory ? $clothingCategory->id : null,
            'name' => 'Out of Stock T-Shirt'
        ]);
        Product::factory()->count(1)->inactive()->create([
            'category_id' => $electronicsCategory ? $electronicsCategory->id : null,
            'name' => 'Inactive Gadget'
        ]);
    }
}
