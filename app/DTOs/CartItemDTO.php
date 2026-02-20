<?php

namespace App\DTOs;

readonly class CartItemDTO
{
    /**
     * @param int $productId The unique identifier for the product.
     * @param string $name The name of the product.
     * @param float $price The unit price of the product.
     * @param string $category The category of the product (e.g., 'clothing', 'footwear').
     * @param int $qty The quantity of this product in the cart.
     */
    public function __construct(
        public int $productId,
        public string $name,
        public float $price,
        public string $category,
        public int $qty,
    ) {}
}