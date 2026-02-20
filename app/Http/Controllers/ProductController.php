<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of active products.
     */
    public function index(): JsonResponse
    {
        // Fetch only active products that are in stock
        $products = Product::where('is_active', true)
                           ->where('stock_quantity', '>', 0)
                           ->with('category')
                           ->orderBy('name')
                           ->get();

        return response()->json(ProductResource::collection($products), Response::HTTP_OK);
    }
}