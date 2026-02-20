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
            'product_id' => $this->id, 
            'name' => $this->name,
            'price' => (float) $this->price,
            'category' => $this->category->name, 
            'sku' => $this->sku,
            'stock_quantity' => $this->stock_quantity,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
