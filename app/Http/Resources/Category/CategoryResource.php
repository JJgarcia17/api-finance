<?php

namespace App\Http\Resources\Category;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'type' => $this->type,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Campos adicionales útiles
            'type_label' => $this->getTypeLabel(),
            'status' => $this->is_active ? 'Activa' : 'Inactiva',
            
            // Información del usuario (solo si está cargada la relación)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            
            // Metadatos útiles
            'meta' => [
                'can_edit' => $this->user_id === auth('sanctum')->id(),
                'can_delete' => $this->user_id === auth('sanctum')->id(),
            ]
        ];
    }
    
    /**
     * Get the type label in Spanish
     */
    private function getTypeLabel(): string
    {
        return match($this->type) {
            'income' => 'Ingreso',
            'expense' => 'Gasto',
            default => ucfirst($this->type)
        };
    }
}
