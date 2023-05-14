<?php

use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Middleware\Admin;
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

// /api/...

Route::apiResource('products', App\Http\Controllers\ProductController::class)
    ->only(['index', 'show']);
Route::apiResource('categories', App\Http\Controllers\CategoryController::class)
    ->only(['index', 'show']);

// Route::get('/orders/{uuid}', [\App\Http\Controllers\OrderController::class, 'show']);
// Route::apiResource('/orders', \App\Http\Controllers\OrderController::class)
//     ->except(['destroy', 'update']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('/orders', \App\Http\Controllers\OrderController::class)
        ->except(['destroy', 'update']);
    // cart
    // checkout

    // /api/admin/...
    Route::prefix('admin')->middleware(Admin::class)->group(function () {

        Route::get('dashboard', DashboardController::class);

        Route::apiResources([
            'products' => ProductController::class,
            'categories' => CategoryController::class,
            'customers' => CustomerController::class,
            'orders' => OrderController::class,
        ]);

        Route::put('customers/{id}/restore', [CustomerController::class, 'restore']);
        Route::put('orders/{id}/restore', [OrderController::class, 'restore']);
        Route::delete('media/{id}', [ProductController::class, 'deleteMedia']);

        Route::apiResource('attachments', AttachmentController::class)
            ->only(['store', 'destroy']);
    });
});
