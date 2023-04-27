<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// /api/admin/...
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::apiResources([
        'products' => ProductController::class,
        'categories' => CategoryController::class,
        'customers' => CustomerController::class,
        'orders' => OrderController::class,
    ]);

    Route::put('customers/{id}/restore', [CustomerController::class, 'restore']);
    Route::put('orders/{id}/restore', [OrderController::class, 'restore']);

    Route::apiResource('attachments', AttachmentController::class)
        ->only(['store', 'destroy']);
});
