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
            'images' => $this->whenNotNull($this->images),
        ];
    }
}
