<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/cart/calculate', [CartController::class, 'calculate']);
// New product listing endpoint
Route::get('/products', [ProductController::class, 'index']);
