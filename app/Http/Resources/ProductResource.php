<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'excerpt' => $this->whenNotNull($this->excerpt),
            'description' => $this->whenNotNull($this->description),
            'is_published' => $this->is_published,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'old_price' => $this->whenNotNull($this->old_price),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'order_product' => $this->whenPivotLoaded('order_product', function () {
                return [
                    'quantity' => $this->pivot->quantity,
                    'unit_price' => $this->pivot->unit_price,
                ];
            }),
            'orders_count' => $this->whenNotNull($this->orders_count),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
