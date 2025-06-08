<?php

namespace App\Http\Resources\Category;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return CategoryResource::collection($this->collection)->toArray($request);
    }
    
    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'total' => $this->collection->count(),
                'active_count' => $this->collection->where('is_active', '=', true)->count(),
                'inactive_count' => $this->collection->where('is_active', '=', false)->count(),
                'income_count' => $this->collection->where('type', '=', 'income')->count(),
                'expense_count' => $this->collection->where('type', '=', 'expense')->count(),
            ],
            'summary' => [
                'types' => [
                    'income' => $this->collection->where('type', '=', 'income')->count(),
                    'expense' => $this->collection->where('type', '=', 'expense')->count(),
                ],
                'status' => [
                    'active' => $this->collection->where('is_active', '=', true)->count(),
                    'inactive' => $this->collection->where('is_active', '=', false)->count(),
                ]
            ]
        ];
    }
}
