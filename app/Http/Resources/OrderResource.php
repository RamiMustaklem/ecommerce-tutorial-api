<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'uuid' => $this->uuid,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'notes' => $this->whenNotNull($this->notes),
            'customer' => new CustomerResource(
                $this->whenLoaded('customer')
            ),
            'products' => ProductResource::collection(
                $this->whenLoaded('products')
            ),
            'order_product' => $this->whenPivotLoaded('order_product', function () {
                return [
                    'quantity' => $this->pivot->quantity,
                    'unit_price' => $this->pivot->unit_price,
                ];
            }),
            'products_count' => $this->whenNotNull($this->products_count),
        ];
    }
}
