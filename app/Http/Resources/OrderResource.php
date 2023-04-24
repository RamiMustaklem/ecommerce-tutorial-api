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
        ];
    }
}
