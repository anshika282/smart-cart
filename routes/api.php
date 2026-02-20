<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/cart/calculate', [CartController::class, 'calculate']);
