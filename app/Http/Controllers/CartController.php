<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DTOs\CartDTO;
use App\DTOs\CartItemDTO;
use App\Services\CartPricingService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
     /**
     * Inject the CartPricingService into the controller.
     * This uses Laravel's automatic dependency injection.
     */
    public function __construct(
        private CartPricingService $pricingService
    ) {}

     public function calculate(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // 1. Validate the incoming request payload.
            $validatedData = $request->validate([
                'cart' => ['required', 'array'],
                'cart.*.product_id' => ['required', 'integer', 'min:1'],
                'cart.*.name' => ['required', 'string', 'max:255'],
                'cart.*.price' => ['required', 'numeric', 'min:0'],
                'cart.*.category' => ['required', 'string', 'max:255'],
                'cart.*.qty' => ['required', 'integer', 'min:1'],
                'coupon_code' => ['nullable', 'string', 'max:50']
            ]);

            $cartItemsDTOs = array_map(function (array $item) {
                return new CartItemDTO(
                    productId: (int) $item['product_id'],
                    name: (string) $item['name'],
                    price: (float) $item['price'],
                    category: strtolower((string) $item['category']),
                    qty: (int) $item['qty']
                );
            }, $validatedData['cart']);

            $cartDTO = new CartDTO(
                items: $cartItemsDTOs,
                couponCode: $validatedData['coupon_code'] ?? null
            );

            // 3.the core business logic to the CartPricingService.
            $result = $this->pricingService->calculateFinalPricing($cartDTO);

            // 4. Return a JSON response.
            return response()->json($result, Response::HTTP_OK);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            \Log::error('Cart calculation failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'An unexpected error occurred during cart calculation.',
                'error' => $e->getMessage() 
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
