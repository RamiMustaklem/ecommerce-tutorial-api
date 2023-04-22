<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
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
        // orders
    ]);

    Route::put('customers/{id}/restore', [CustomerController::class, 'restore']);
});
