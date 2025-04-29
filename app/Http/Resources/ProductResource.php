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
            'description' => $this->description,
            'img_path' => $this->img_path,
            'expiration' => $this->expiration,
            'product_in' => $this->producted_in,
            'import_cycle' => $this->import_cycle,
            'unit' => $this->unit,
            'price_unit' => $this->price_unit,
            'average' => $this->average,
            'variance' => $this->variance,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
            // 'type_product' => new TypeResource($this->whenLoaded('productType')), 
            // 'type'=> $this->ProductType ? $this->ProductType->name : null,
        ];
    }
}
