<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryDiscount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $categoriesData = [
            ['name' => 'Clothing', 'discount_percentage' => 10.00],
            ['name' => 'Footwear', 'discount_percentage' => 5.00],
            ['name' => 'Electronics', 'discount_percentage' => 0.00],
            ['name' => 'Books', 'discount_percentage' => 15.00], 
            ['name' => 'Groceries', 'discount_percentage' => 2.00],
        ];

        foreach ($categoriesData as $data) {
            $category = Category::firstOrCreate(
                ['name' => $data['name']],
                ['is_active' => true]
            );

            // Create or update category discount for this category
            CategoryDiscount::updateOrCreate(
                ['category_id' => $category->id],
                ['discount_percentage' => $data['discount_percentage'], 'is_active' => true]
            );
        }
    }
}
