<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // NEWUSER coupon (Flat ₹200 off)
        Coupon::factory()->newuser()->create();

        // FESTIVE coupon (15% off, max ₹500)
        Coupon::factory()->festive()->create();

        // Some other random coupons for testing
        Coupon::factory()->count(5)->create();

        // Example: A fixed coupon with a minimum cart amount
        Coupon::factory()->fixed(100.00)->create([
            'code' => 'MINCART100',
            'min_cart_amount' => 1000.00,
        ]);

        // Example: A percentage coupon with a usage limit
        Coupon::factory()->percent(10.00, 300.00)->create([
            'code' => 'LIMITED10',
            'usage_limit' => 50,
        ]);
    }
}
