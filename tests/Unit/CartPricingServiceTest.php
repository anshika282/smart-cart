<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CartPricingService;
use App\DTOs\CartDTO;
use App\DTOs\CartItemDTO;
use App\Models\CategoryDiscount;
use Mockery;

class CartPricingServiceTest extends TestCase
{
    private CartPricingService $service;
    private $mockCategoryDiscount; 
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCategoryDiscount = Mockery::mock(CategoryDiscount::class);
        $this->service = new CartPricingService($this->mockCategoryDiscount);

        $this->mockCategoryDiscounts(
            [
                ['name' => 'Clothing', 'discount_percentage' => 10.00],
                ['name' => 'Footwear', 'discount_percentage' => 5.00],
                ['name' => 'Electronics', 'discount_percentage' => 0.00],
            ]
        );
    }

    protected function tearDown(): void
    {
        Mockery::close(); // Clean up Mockery expectations
        parent::tearDown();
    }

    // Helper to set up mock category discounts dynamically for tests
    private function mockCategoryDiscounts(array $discountsData): void
    {
        // Mocking chain: newQuery() -> join() -> whereIn() -> where() -> get()
        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $this->mockCategoryDiscount->shouldReceive('newQuery')->andReturn($mockQuery);

        $mockQuery->shouldReceive('join')->andReturn($mockQuery);
        $mockQuery->shouldReceive('whereIn')->andReturn($mockQuery);
        $mockQuery->shouldReceive('where')->andReturn($mockQuery);

        // Convert data into objects with dynamic properties to match Eloquent's behavior
        $eloquentResults = collect($discountsData)->map(function ($item) {
            $obj = new \stdClass();
            $obj->name = $item['name'];
            $obj->discount_percentage = $item['discount_percentage'];
            return $obj;
        });

        $mockQuery->shouldReceive('get')->andReturn($eloquentResults);
    }

    // --- Helper Methods to create DTOs easily ---
    private function createCartItem(
        int $productId,
        string $name,
        float $price,
        string $category,
        int $qty
    ): CartItemDTO {
        return new CartItemDTO($productId, $name, $price, $category, $qty);
    }

    private function createCartDTO(array $items, ?string $couponCode = null): CartDTO
    {
        return new CartDTO($items, $couponCode);
    }

    // --- Test Cases ---

    /** @test */
    public function it_calculates_subtotal_correctly_for_simple_cart()
    {
        $cartItems = [
            $this->createCartItem(101, 'T-Shirt', 500, 'clothing', 2),
            $this->createCartItem(205, 'Shoes', 2000, 'footwear', 1),
        ];
        $cartDTO = $this->createCartDTO($cartItems);

        $result = $this->service->calculateFinalPricing($cartDTO);

        $this->assertEquals(3000.00, $result['subtotal']);
        // (500*2)*0.10 = 100 (clothing)
        // (2000*1)*0.05 = 100 (footwear)
        $this->assertEquals(200.00, $result['discounts']['category_discount']);
        $this->assertEquals(0.00, $result['discounts']['quantity_discount']);
        $this->assertEquals(0.00, $result['discounts']['coupon_discount']);
        // Current subtotal: 3000 - 200 = 2800
        // Tax: 2800 * 0.18 = 504
        $this->assertEquals(504.00, $result['tax']);
        // Shipping: 2800 < 3000, so 100
        $this->assertEquals(100.00, $result['shipping']);
        // Final: 2800 + 504 + 100 = 3404
        $this->assertEquals(3404.00, $result['final_amount']);
    }

    /** @test */
    public function it_handles_an_empty_cart()
    {
        $cartDTO = $this->createCartDTO([]); // Empty array for items

        $result = $this->service->calculateFinalPricing($cartDTO);

        $this->assertEquals(0.00, $result['subtotal']);
        $this->assertEquals(0.00, $result['discounts']['category_discount']);
        $this->assertEquals(0.00, $result['discounts']['quantity_discount']);
        $this->assertEquals(0.00, $result['discounts']['coupon_discount']);
        $this->assertEquals(0.00, $result['tax']);
        $this->assertEquals(0.00, $result['shipping']);
        $this->assertEquals(0.00, $result['final_amount']);
    }

    /** @test */
    public function it_applies_category_discounts_correctly()
    {
        $cartItems = [
            $this->createCartItem(101, 'Fancy Dress', 1000, 'clothing', 1), // 10% off = 100
            $this->createCartItem(201, 'Sneakers', 500, 'footwear', 2),    // 5% off = 50
            $this->createCartItem(301, 'Headphones', 2000, 'electronics', 1),// 0% off = 0
        ];
        $cartDTO = $this->createCartDTO($cartItems);

        $result = $this->service->calculateFinalPricing($cartDTO);

        $this->assertEquals(4000.00, $result['subtotal']);
        $this->assertEquals(150.00, $result['discounts']['category_discount']); // 100 + 50 + 0

        // Subtotal after category discounts: 4000 - 150 = 3850
        // Total quantity = 1+2+1 = 4 (no quantity discount)
        // Tax: 3850 * 0.18 = 693
        // Shipping: 0 (3850 >= 3000)
        // Final: 3850 + 693 + 0 = 4543
        $this->assertEquals(0.00, $result['discounts']['quantity_discount']);
        $this->assertEquals(0.00, $result['discounts']['coupon_discount']);
        $this->assertEquals(693.00, $result['tax']);
        $this->assertEquals(0.00, $result['shipping']);
        $this->assertEquals(4543.00, $result['final_amount']);
    }

    /** @test */
    public function it_applies_quantity_discount_when_threshold_is_met()
    {
        $cartItems = [
            $this->createCartItem(101, 'Basic T-Shirt', 100, 'clothing', 3), // Sub: 300, CatDisc: 30
            $this->createCartItem(102, 'Casual Shirt', 200, 'clothing', 2),  // Sub: 400, CatDisc: 40
        ]; // Total raw subtotal = 300 + 400 = 700. Total quantity = 5 (>= 5 threshold)
        $cartDTO = $this->createCartDTO($cartItems);

        $result = $this->service->calculateFinalPricing($cartDTO);

        // Corrected assertion: raw subtotal is 700
        $this->assertEquals(700.00, $result['subtotal']);
        // Category discounts: 30 + 40 = 70
        $this->assertEquals(70.00, $result['discounts']['category_discount']);

        // Subtotal after category discounts: 700 - 70 = 630
        // Quantity discount: 630 * 0.05 = 31.50
        $this->assertEquals(31.50, $result['discounts']['quantity_discount']);

        // Subtotal after all discounts: 630 - 31.50 = 598.50
        // Tax: 598.50 * 0.18 = 107.73
        // Shipping: 100 (598.50 < 3000)
        // Final: 598.50 + 107.73 + 100 = 806.23
        $this->assertEquals(0.00, $result['discounts']['coupon_discount']);
        $this->assertEquals(107.73, $result['tax']);
        $this->assertEquals(100.00, $result['shipping']);
        $this->assertEquals(806.23, $result['final_amount']);
    }

    /** @test */
    public function it_applies_newuser_coupon_for_flat_discount()
    {
        $cartItems = [
            $this->createCartItem(101, 'T-Shirt', 500, 'clothing', 1), // 10% off = 50
        ];
        $cartDTO = $this->createCartDTO($cartItems, 'NEWUSER');

        $result = $this->service->calculateFinalPricing($cartDTO);

        $this->assertEquals(500.00, $result['subtotal']);
        $this->assertEquals(50.00, $result['discounts']['category_discount']);

        // Subtotal after category: 500 - 50 = 450
        // Coupon NEWUSER: 200 flat
        $this->assertEquals(200.00, $result['discounts']['coupon_discount']);

        // Subtotal after all discounts: 450 - 200 = 250
        // Tax: 250 * 0.18 = 45
        // Shipping: 100 (250 < 3000)
        // Final: 250 + 45 + 100 = 395
        $this->assertEquals(45.00, $result['tax']);
        $this->assertEquals(100.00, $result['shipping']);
        $this->assertEquals(395.00, $result['final_amount']);
    }

    /** @test */
    public function it_applies_festive_coupon_with_percentage_and_cap()
    {
        // Scenario 1: Discount is below cap
        $cartItems1 = [
            $this->createCartItem(101, 'Product A', 1000, 'clothing', 1), // 10% off = 100
            $this->createCartItem(102, 'Product B', 1000, 'footwear', 1), // 5% off = 50
        ]; // Raw Subtotal: 2000
        $cartDTO1 = $this->createCartDTO($cartItems1, 'FESTIVE');

        $result1 = $this->service->calculateFinalPricing($cartDTO1);

        $this->assertEquals(2000.00, $result1['subtotal']);
        $this->assertEquals(150.00, $result1['discounts']['category_discount']); // 100 + 50 = 150
        // Subtotal after category: 2000 - 150 = 1850
        // FESTIVE: 1850 * 0.15 = 277.50 (below 500 cap)
        $this->assertEquals(277.50, $result1['discounts']['coupon_discount']);

        // Subtotal after all discounts: 1850 - 277.50 = 1572.50
        // Tax: 1572.50 * 0.18 = 283.05
        // Shipping: 100
        // Final: 1572.50 + 283.05 + 100 = 1955.55
        $this->assertEquals(283.05, $result1['tax']);
        $this->assertEquals(100.00, $result1['shipping']);
        $this->assertEquals(1955.55, $result1['final_amount']);

        // Scenario 2: Discount hits the cap
        $cartItems2 = [
            $this->createCartItem(101, 'High Value Item', 5000, 'electronics', 1), // 0% off = 0
        ]; // Raw Subtotal: 5000
        $cartDTO2 = $this->createCartDTO($cartItems2, 'FESTIVE');

        $result2 = $this->service->calculateFinalPricing($cartDTO2);

        $this->assertEquals(5000.00, $result2['subtotal']);
        $this->assertEquals(0.00, $result2['discounts']['category_discount']);
        // Subtotal after category: 5000
        // FESTIVE: 5000 * 0.15 = 750 (hits 500 cap)
        $this->assertEquals(500.00, $result2['discounts']['coupon_discount']);

        // Subtotal after all discounts: 5000 - 500 = 4500
        // Tax: 4500 * 0.18 = 810
        // Shipping: 0 (4500 >= 3000)
        // Final: 4500 + 810 + 0 = 5310
        $this->assertEquals(810.00, $result2['tax']);
        $this->assertEquals(0.00, $result2['shipping']);
        $this->assertEquals(5310.00, $result2['final_amount']);
    }

    /** @test */
    public function it_ignores_invalid_coupon_codes()
    {
        $cartItems = [
            $this->createCartItem(101, 'T-Shirt', 500, 'clothing', 1),
        ];
        $cartDTO = $this->createCartDTO($cartItems, 'INVALIDCOUPON');

        $result = $this->service->calculateFinalPricing($cartDTO);

        $this->assertEquals(0.00, $result['discounts']['coupon_discount']);
        // The rest of the calculation should be as if no coupon was applied
        // Subtotal: 500
        // Category discount: 50
        // Subtotal after category: 450
        // Tax: 450 * 0.18 = 81
        // Shipping: 100
        // Final: 450 + 81 + 100 = 631
        $this->assertEquals(81.00, $result['tax']);
        $this->assertEquals(100.00, $result['shipping']);
        $this->assertEquals(631.00, $result['final_amount']);
    }

    /** @test */
    public function it_applies_shipping_correctly_based_on_threshold()
    {
        // Scenario 1: Subtotal after discounts >= 3000 (Free shipping)
        $cartItems1 = [
            $this->createCartItem(101, 'Expensive Gadget', 3500, 'electronics', 1), // 0% disc
        ];
        $cartDTO1 = $this->createCartDTO($cartItems1);

        $result1 = $this->service->calculateFinalPricing($cartDTO1);

        // Subtotal: 3500
        // Tax: 3500 * 0.18 = 630
        // Final: 3500 + 630 + 0 = 4130
        $this->assertEquals(0.00, $result1['shipping']);
        $this->assertEquals(4130.00, $result1['final_amount']);

        // Scenario 2: Subtotal after discounts < 3000 (100 shipping)
        $cartItems2 = [
            $this->createCartItem(101, 'Moderately Priced Item', 2500, 'electronics', 1), // 0% disc
        ];
        $cartDTO2 = $this->createCartDTO($cartItems2);

        $result2 = $this->service->calculateFinalPricing($cartDTO2);

        // Subtotal: 2500
        // Tax: 2500 * 0.18 = 450
        // Final: 2500 + 450 + 100 = 3050
        $this->assertEquals(100.00, $result2['shipping']);
        $this->assertEquals(3050.00, $result2['final_amount']);
    }

    /** @test */
    public function it_handles_duplicate_products_by_aggregating_quantities()
    {
        $cartItems = [
            $this->createCartItem(101, 'T-Shirt', 500, 'clothing', 1), // Raw 500, Cat Disc 50
            $this->createCartItem(205, 'Shoes', 2000, 'footwear', 1), // Raw 2000, Cat Disc 100
            $this->createCartItem(101, 'T-Shirt', 500, 'clothing', 2), // Should sum with first T-Shirt: total 3 T-Shirts
        ];
        // Expected: 1xShoes (2000), 3xT-Shirt (1500)
        // Raw Subtotal: 2000 + 1500 = 3500
        // Category Discounts: (500*3)*0.10 + (2000*1)*0.05 = 150 + 100 = 250
        // Subtotal after category: 3500 - 250 = 3250
        // Total Quantity: 1 + 3 = 4 (no quantity discount)
        // Tax: 3250 * 0.18 = 585
        // Shipping: 0 (3250 >= 3000)
        // Final: 3250 + 585 + 0 = 3835
        $cartDTO = $this->createCartDTO($cartItems);

        $result = $this->service->calculateFinalPricing($cartDTO);

        $this->assertEquals(3500.00, $result['subtotal']);
        $this->assertEquals(250.00, $result['discounts']['category_discount']);
        $this->assertEquals(0.00, $result['discounts']['quantity_discount']);
        $this->assertEquals(0.00, $result['discounts']['coupon_discount']);
        $this->assertEquals(585.00, $result['tax']);
        $this->assertEquals(0.00, $result['shipping']);
        $this->assertEquals(3835.00, $result['final_amount']);
    }

    /** @test */
    public function it_prevents_final_price_from_being_negative_due_to_coupon()
    {
        $cartItems = [
            $this->createCartItem(101, 'Cheap Item', 100, 'clothing', 1), // 10% off = 10
        ]; // Raw Subtotal: 100
        $cartDTO = $this->createCartDTO($cartItems, 'NEWUSER'); // NEWUSER is flat 200

        $result = $this->service->calculateFinalPricing($cartDTO);

        $this->assertEquals(100.00, $result['subtotal']);
        $this->assertEquals(10.00, $result['discounts']['category_discount']);

        // Subtotal after category: 100 - 10 = 90
        // Coupon tries to apply 200, but only 90 can be discounted.
        $this->assertEquals(90.00, $result['discounts']['coupon_discount']);

        // Subtotal after all discounts: 90 - 90 = 0
        // Tax: 0 * 0.18 = 0
        // Shipping: 0 (because $currentSubtotal <= 0.00, so free shipping)
        // Final: 0 + 0 + 0 = 0
        $this->assertEquals(0.00, $result['tax']);
        $this->assertEquals(0.00, $result['shipping']); // Corrected: shipping is 0 if subtotal is 0
        $this->assertEquals(0.00, $result['final_amount']); // Corrected: final amount is 0 if subtotal is 0
    }

    /** @test */
    public function it_ensures_correct_rounding_of_decimals()
    {
        $cartItems = [
            $this->createCartItem(101, 'Item 1', 99.99, 'clothing', 1), // 10% off = 9.999 -> 10.00
            $this->createCartItem(102, 'Item 2', 15.33, 'footwear', 3), // 5% off (15.33*3)*0.05 = 2.2995 -> 2.30
        ]; // Raw Subtotal: 99.99 + (15.33 * 3) = 99.99 + 45.99 = 145.98
        // Cat Disc: 10.00 + 2.30 = 12.30
        // Subtotal after cat disc: 145.98 - 12.30 = 133.68
        // Total Qty: 1 + 3 = 4 (no qty disc)
        // No coupon
        // Tax: 133.68 * 0.18 = 24.0624 -> 24.06
        // Shipping: 100 (133.68 < 3000)
        // Final: 133.68 + 24.06 + 100 = 257.74
        $cartDTO = $this->createCartDTO($cartItems);

        $result = $this->service->calculateFinalPricing($cartDTO);

        $this->assertEquals(145.98, $result['subtotal']);
        $this->assertEquals(12.30, $result['discounts']['category_discount']);
        $this->assertEquals(24.06, $result['tax']);
        $this->assertEquals(100.00, $result['shipping']);
        $this->assertEquals(257.74, $result['final_amount']);
    }

    /** @test */
    public function it_applies_all_discounts_and_rules_in_correct_order()
    {
        $cartItems = [
            $this->createCartItem(1, 'Clothing Item', 1000, 'clothing', 3), // Sub: 3000, CatDisc: 300
            $this->createCartItem(2, 'Footwear Item', 500, 'footwear', 2),   // Sub: 1000, CatDisc: 50
        ]; // Raw Subtotal: 4000. Total Qty: 5.
        $cartDTO = $this->createCartDTO($cartItems, 'FESTIVE'); // FESTIVE: 15% off, max 500

        $result = $this->service->calculateFinalPricing($cartDTO);

        // 1. Raw Subtotal: (1000*3) + (500*2) = 3000 + 1000 = 4000.00
        $this->assertEquals(4000.00, $result['subtotal']);

        // 2. Category Discount: (3000 * 0.10) + (1000 * 0.05) = 300 + 50 = 350.00
        $this->assertEquals(350.00, $result['discounts']['category_discount']);

        // Current Subtotal: 4000 - 350 = 3650.00

        // 3. Quantity Discount: Total Qty = 5 (>= threshold), so 5% of 3650 = 182.50
        $this->assertEquals(182.50, $result['discounts']['quantity_discount']);

        // Current Subtotal: 3650 - 182.50 = 3467.50

        // 4. Coupon Discount (FESTIVE 15% off, max 500):
        //    3467.50 * 0.15 = 520.125. Capped at 500.00.
        $this->assertEquals(500.00, $result['discounts']['coupon_discount']);

        // Current Subtotal: 3467.50 - 500 = 2967.50

        // 5. Tax: 2967.50 * 0.18 = 534.15
        $this->assertEquals(534.15, $result['tax']);

        // 6. Shipping: Current Subtotal (2967.50) < 3000, so 100.00
        $this->assertEquals(100.00, $result['shipping']);

        // 7. Final Amount: 2967.50 + 534.15 + 100.00 = 3601.65
        $this->assertEquals(3601.65, $result['final_amount']);
    }
}