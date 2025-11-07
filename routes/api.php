<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

Route::prefix('api')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::prefix('api')->middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class,'logout']);
    Route::get('me', [AuthController::class,'me']);
    Route::get('products/search', [ProductController::class,'productSearch']);
    Route::get('products', [ProductController::class,'index']);
    Route::get('products/{id}', [ProductController::class,'show']);
    Route::get('orders', [OrderController::class,'index']);
    Route::post('orders', [OrderController::class,'store']);
    Route::get('orders/{id}', [OrderController::class,'show']);
    Route::put('orders/{id}', [OrderController::class,'update']);
    Route::delete('orders/{id}', [OrderController::class,'destroy']);
    Route::post('orders/{id}/payment', [OrderController::class,'Payment']);
    Route::post('orders/{id}/place', [OrderController::class,'place']);
});