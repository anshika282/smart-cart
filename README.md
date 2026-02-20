# Smart Cart Pricing Engine (Laravel PHP)

This project implements a simple cart pricing engine using Laravel. It calculates the final payable amount for a shopping cart by applying multiple rules, including category-specific discounts, quantity-based discounts, coupon codes, tax, and shipping, all fetched dynamically from a database. The frontend is a simple HTML/JavaScript page for demonstration and interaction.

## Assumptions Made in the Code

To maintain focus and adhere to the project scope, the following assumptions have been made:

*   **Category Naming:** Category names (e.g., "Clothing", "Footwear") should be consistent. The `CartItemDTO` converts categories to lowercase upon mapping, and the service uses this lowercase form for database lookups.
*   **Coupon Code Matching:** Coupon codes (`NEWUSER`, `FESTIVE`) are treated as case-insensitive during matching (`strtoupper` is used).Also if no coupon are added then the coupon discount is zero. The coupon feild is not kept as required in fe.
*   **Active Rules:** Only `is_active = true` records for `category_discounts` are considered. Products fetched for the frontend are `is_active = true` and `stock_quantity > 0`.
*   **Price/Quantity Validation:** Frontend inputs for price and quantity are validated by the controller to be non-negative (`min:0` for price) and positive (`min:1` for quantity) before reaching the service layer.
*   **Decimal Precision:** All monetary calculations are handled with `DECIMAL(10,2)` in the database and `round($value, 2)` in PHP to ensure accurate financial results and prevent floating-point errors.
*   **Shipping for Zero Subtotal:** If the cart's subtotal after all discounts is zero or negative, shipping is set to `0.00`.

## Discount & Calculation Order Criteria

The business rules are applied in the following strict order to determine the final payable amount:

1.  **Category Discount:**
    *   Applied **per product item**.
    *   Rates are fetched from the `category_discounts` table.

2.  **Quantity Discount:**
    *   Applied to the **subtotal after category discounts**.
    *   If the total quantity of all items in the cart is $\ge$ 5, an additional 5% discount is applied.

3.  **Coupon Discount:**
    *   Applied to the **subtotal after category and quantity d
    *   The coupon discount amount will never reduce the subtotal below zero.

4.  **Tax Calculation (GST):**
    *   Applied at **18%** (GST @ 18%).
    *   Calculated on the **subtotal after all discounts (category, quantity, and coupon)**.

5.  **Shipping Charges:**
    *   Based on the **subtotal after all discounts**.
    *   If subtotal $\ge$ ₹3000: Free shipping (`₹0`).
    *   If subtotal $< $₹3000: Shipping fee of `₹100`.
    *   If subtotal $\le$ ₹0: Free shipping (`₹0`).

## API Endpoints

The following API endpoints are exposed by the Laravel backend:

*   **`GET /api/products`**
    *   **Description:** Fetches a list of all active and in-stock products, along with their category information. This is used by the frontend to populate the product selection dropdown.
    *   **Response:** A JSON array of product objects, formatted using `ProductResource`.
    *   **Example Response (truncated):**
        ```json
        [
            {
                "product_id": 8,
                "name": "accusamus aut",
                "price": 4777.40,
                "category": "Clothing",
                "sku": "IOU-1270",
                "stock_quantity": 733,
                "is_active": true
            }
            // ... more products
        ]
        ```

*   **`POST /api/cart/calculate`**
    *   **Description:** Receives the current cart contents (products and quantities) and an optional coupon code. It then applies all business rules to calculate the final pricing details.
    *   **Request Body (JSON):**
        ```json
        {
            "cart": [
                {
                    "product_id": 101,
                    "name": "T-Shirt",
                    "price": 500,
                    "category": "clothing",
                    "qty": 2
                },
                {
                    "product_id": 205,
                    "name": "Shoes",
                    "price": 2000,
                    "category": "footwear",
                    "qty": 1
                }
            ],
            "coupon_code": "NEWUSER"
        }
        ```
    *   **Response (JSON):** The structured output of the calculation.
        ```json
        {
            "subtotal": 3000.00,
            "discounts": {
                "category_discount": 350.00,
                "quantity_discount": 150.00,
                "coupon_discount": 200.00
            },
            "tax": 414.00,
            "shipping": 0.00,
            "final_amount": 3064.00
        }
        ```

## Project Structure

The project follows a standard Laravel directory structure, with key additions for the pricing engine highlighted:

```
.
├── app/
│   ├── DTOs/
│   │   ├── CartDTO.php             # DTO for the entire cart payload
│   │   └── CartItemDTO.php         # DTO for individual items in the cart
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ProductController.php  # Handles GET /api/products
│   │   │   └── CartController.php         # Handles POST /api/cart/calculate
│   │   └── Resources/
│   │       └── ProductResource.php        # Transforms Product Model to API-friendly format
│   ├── Models/
│   │   ├── Category.php            # Eloquent model for 'categories' table
│   │   ├── CategoryDiscount.php    # Eloquent model for 'category_discounts' table
│   │   ├── Coupon.php              # Eloquent model for 'coupons' table
│   │   ├── Order.php               # Eloquent model for 'orders' table
│   │   ├── OrderItem.php           # Eloquent model for 'order_items' table
│   │   └── Product.php             # Eloquent model for 'products' table
│   └── Services/
│       └── CartPricingService.php  # The core business logic for cart calculations
├── database/
│   ├── factories/                  # Factories for seeding test data
│   │   ├── CategoryDiscountFactory.php
│   │   ├── CategoryFactory.php
│   │   ├── CouponFactory.php
│   │   └── ProductFactory.php
│   ├── migrations/                 # Database schema definitions
│   │   ├── 2024_01_01_000001_create_categories_table.php
│   │   ├── 2024_01_01_000002_create_products_table.php
│   │   ├── 2024_01_01_000003_create_coupons_table.php
│   │   └── 2024_01_01_000004_create_orders_table.php
│   └── seeders/                    # Seeders to populate the database with dummy data
│       ├── CategorySeeder.php
│       ├── CouponSeeder.php
│       ├── DatabaseSeeder.php      # Main seeder that calls others
│       └── ProductSeeder.php
├── resources/
│   └── views/
│       └── welcome.blade.php       # Contains the interactive frontend HTML/JavaScript
├── routes/
│   └── api.php                     # API route definitions
├── tests/
│   └── Unit/
│       └── CartPricingServiceTest.php # Unit tests for the CartPricingService
```

## Setup and Execution Instructions

1.  **After clonong and initial setup migrate and seed the databse:**
    ```bash
    php artisan migrate
    php artisan db:seed
    ```
    This will typically start the server on `http://localhost:8000`.

2.  **Start the Laravel Development Server:**
    ```bash
    php artisan serve
    ```
    This will typically start the server on `http://localhost:8000`.

3.  **Access the Frontend:**
    Open your web browser and navigate to:
    ```
    http://localhost:8000/
    ```
---

This README provides a comprehensive overview for anyone looking to understand, set up, and test your Smart Cart Pricing Engine.
