<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Attachment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ProductResource::collection(
            Product::with(['categories'])
                ->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductCreateRequest $request)
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create($request->safe()->except('images'));

            if ($request->has('images')) {
                // extract attachment id/s
                $attachmentIds = $request->collect('images')->pluck('id');
                // query all attachment models
                // get each media item and
                // perform media move from attachment to product
                $attachments = Attachment::whereIn('id', $attachmentIds)
                    ->each(function (Attachment $attachment) use ($product) {
                        $mediaItem = $attachment->getMedia()->first();
                        $movedMediaItem = $mediaItem->move($product);
                    });
            }

            return $product;
        });

        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load('categories', 'media');

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        $product = DB::transaction(function () use ($request, $product) {
            $product->update($request->safe()->except('images'));

            if ($request->has('images')) {
                // extract attachment id/s
                $attachmentIds = $request->collect('images')->pluck('id');
                // query all attachment models
                // get each media item and
                // perform media move from attachment to product
                $attachments = Attachment::whereIn('id', $attachmentIds)
                    ->each(function (Attachment $attachment) use ($product) {
                        $mediaItem = $attachment->getMedia()->first();
                        $movedMediaItem = $mediaItem->move($product);
                    });
            }

            return $product;
        });

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        return $product->delete();
    }

    public function deleteMedia(Media $media)
    {
        return $media->delete();
    }
}
