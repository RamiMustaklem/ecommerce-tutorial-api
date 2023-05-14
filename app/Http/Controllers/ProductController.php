<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return ProductResource::collection(
            Product::isPublished()
                ->with('categories:slug,name', 'media')
                ->paginate()
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        abort_if(!$product->is_published, Response::HTTP_NOT_FOUND, 'Product not found.');

        $product->load('categories:slug,name', 'media');

        return new ProductResource($product);
    }
}
