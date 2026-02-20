<?php

namespace App\Services;

use App\DTOs\CartDTO;
use App\DTOs\CartItemDTO;
use App\Models\CategoryDiscount; 

class CartPricingService
{
    
    private const TAX_RATE = 0.18;
    private const QTY_DISCOUNT_THRESHOLD = 5;
    private const QTY_DISCOUNT_RATE = 0.05; 
    private const SHIPPING_THRESHOLD = 3000.00;
    private const SHIPPING_FEE = 100.00;

    private const CATEGORY_DISCOUNTS = [
        'clothing' => 0.10, 
        'footwear' => 0.05, 
        'electronics' => 0.00
    ];

    private array $cachedCategoryDiscounts = [];

    public function __construct(
        private CategoryDiscount $categoryDiscountModel // Using the model directly
    ) {}

    /**
     * Main engine method to calculate the final pricing for a given cart.
     *
     * @param CartDTO $cartDTO The Data Transfer Object representing the cart and coupon.
     * @return array The calculated pricing details in a structured array.
     */
    public function calculateFinalPricing(CartDTO $cartDTO): array
    {
        // Reset cache for each calculation
        $this->cachedCategoryDiscounts = [];
        // 1. Merge duplicate product items and filter out invalid data.
        $cleanItems = $this->aggregateDuplicateItems($cartDTO->items);

        if (empty($cleanItems)) {
            return $this->getEmptyCartResponse();
        }

        // Calculate total quantity from the clean items.
        $rawSubtotal = $this->calculateRawSubtotal($cleanItems);
        $totalQuantity = array_sum(array_column($cleanItems, 'qty'));

        $currentSubtotal = $rawSubtotal;

        // Initialize discount tracking
        $totalDiscounts = [
            'category_discount' => 0.00,
            'quantity_discount' => 0.00,
            'coupon_discount' => 0.00,
        ];

        // --- Apply Discounts in Order ---

        // 2. Category Discount 
        $categoryDiscountAmount = $this->calculateCategoryDiscount($cleanItems);
        $currentSubtotal = max(0, $currentSubtotal - $categoryDiscountAmount);
        $totalDiscounts['category_discount'] = $categoryDiscountAmount;

        // 3. Quantity Discount
        $quantityDiscountAmount = $this->calculateQuantityDiscount($totalQuantity, $currentSubtotal);
        $currentSubtotal = max(0, $currentSubtotal - $quantityDiscountAmount);
        $totalDiscounts['quantity_discount'] = $quantityDiscountAmount;

        // 4. Coupon Discount
        $couponDiscountAmount = $this->calculateCouponDiscount($cartDTO->couponCode, $currentSubtotal);
        $currentSubtotal = max(0, $currentSubtotal - $couponDiscountAmount);
        $totalDiscounts['coupon_discount'] = $couponDiscountAmount;

        // 5. Tax Calculation 
        $tax = round($currentSubtotal * self::TAX_RATE, 2);

        // 6. Shipping Charges 
        if ($currentSubtotal <= 0) {
                $shipping = 0.00;
            } elseif ($currentSubtotal >= self::SHIPPING_THRESHOLD) {
                $shipping = 0.00;
            } else {
                $shipping = self::SHIPPING_FEE;
            }

        // 7. Final Payable Amount
        $finalAmount = round($currentSubtotal + $tax + $shipping, 2);

        // output.
        return [
            'subtotal' => round($rawSubtotal, 2),
            'discounts' => $totalDiscounts,
            'tax' => $tax,
            'shipping' => $shipping,
            'final_amount' => $finalAmount
        ];
    }

    /**
     * Aggregates duplicate items in the cart by summing their quantities.
     * Assumes items passed are already validated and have positive quantities.
     *
     * @param CartItemDTO[] $items The array of CartItemDTOs from the cart.
     * @return CartItemDTO[] An array of CartItemDTOs with duplicates aggregated.
     */
    private function aggregateDuplicateItems(array $items): array
    {
        $aggregated = [];

        foreach ($items as $item) {
            $id = $item->productId;

            if (isset($aggregated[$id])) {
                //create a new DTO with summed quantity if duplicate found.
                $aggregated[$id] = new CartItemDTO(
                    productId: $item->productId,
                    name: $item->name,
                    price: $item->price,
                    category: $item->category,
                    qty: $aggregated[$id]->qty + $item->qty
                );
            } else {
                $aggregated[$id] = $item; 
            }
        }

        return array_values($aggregated);
    }

    /**
     * Calculates the raw subtotal of the cart before any discounts.
     *
     * @param CartItemDTO[] $items The array of clean CartItemDTOs.
     * @return float The raw subtotal.
     */
    private function calculateRawSubtotal(array $items): float
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($item->price * $item->qty);
        }
        return $subtotal;
    }

    /**
     * Calculates the total category discount amount.
     *
     * @param CartItemDTO[] $items The array of clean CartItemDTOs.
     * @return float The total category discount applied.
     */
    private function calculateCategoryDiscount(array $items): float
    {
        $discount = 0;
        $categoryNames = array_unique(array_column($items, 'category'));
        if (empty($this->cachedCategoryDiscounts)) {
            $discounts = $this->categoryDiscountModel->newQuery()
                                                    ->join('categories', 'category_discounts.category_id', '=', 'categories.id')
                                                    ->whereIn('categories.name', $categoryNames)
                                                    ->where('category_discounts.is_active', true)
                                                    ->get(['categories.name', 'category_discounts.discount_percentage']);

            foreach ($discounts as $disc) {
                $this->cachedCategoryDiscounts[strtolower($disc->name)] = $disc->discount_percentage / 100;
            }
        }

        foreach ($items as $item) {
            // Retrieve discount rate from cache. Default to 0 if category not found or no active discount.
            $rate = $this->cachedCategoryDiscounts[$item->category] ?? 0.00;
            $discount += ($item->price * $item->qty) * $rate;
        }
        return round($discount, 2);
    }

    /**
     * Calculates the quantity-based discount if the total quantity threshold is met.
     *
     * @param int $totalQuantity The total quantity of all items in the cart.
     * @param float $currentSubtotal The subtotal after previous discounts.
     * @return float The quantity discount applied.
     */
    private function calculateQuantityDiscount(int $totalQuantity, float $currentSubtotal): float
    {
        if ($totalQuantity >= self::QTY_DISCOUNT_THRESHOLD) {
            return round($currentSubtotal * self::QTY_DISCOUNT_RATE, 2);
        }
        return 0.00;
    }

    /**
     * Calculates the coupon discount based on the provided coupon code and current subtotal.
     *
     * @param string|null $couponCode The coupon code to apply.
     * @param float $currentSubtotal The subtotal after previous discounts.
     * @return float The coupon discount applied.
     */
    private function calculateCouponDiscount(?string $couponCode, float $currentSubtotal): float
    {
        if (!$couponCode) {
            return 0.00; 
        }

        $discount = match (strtoupper($couponCode)) {
            'NEWUSER' => 200.00, // Flat ₹200 off
            'FESTIVE' => min($currentSubtotal * 0.15, 500.00), 
            default => 0.00, 
        };

        return round(min($discount, $currentSubtotal), 2);
    }

    /**
     * Provides a standard response for an empty cart, all values being zero.
     *
     * @return array The empty cart pricing response.
     */
    private function getEmptyCartResponse(): array
    {
        return [
            'subtotal' => 0.00,
            'discounts' => [
                'category_discount' => 0.00,
                'quantity_discount' => 0.00,
                'coupon_discount' => 0.00,
            ],
            'tax' => 0.00,
            'shipping' => 0.00,
            'final_amount' => 0.00
        ];
    }
}