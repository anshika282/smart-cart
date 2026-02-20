<?php

namespace App\DTOs;

/**
 * Represents the entire cart payload, containing multiple items and an optional coupon code.
 * This DTO is immutable (readonly) to ensure data integrity once created.
 */
readonly class CartDTO
{
    /**
     * @param CartItemDTO[] $items An array of CartItemDTO objects in the cart.
     * @param string|null $couponCode The coupon code applied to the cart, if any.
     */
    public function __construct(
        public array $items,
        public ?string $couponCode = null,
    ) {}
}