<?php

namespace App\Http\Resources\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use \App\Http\Resources\Auth\UserResource;

class AccountResource extends JsonResource
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
            'currency' => $this->currency,
            'initial_balance' => $this->initial_balance,
            'current_balance' => $this->current_balance,
            'color' => $this->color,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'include_in_total' => $this->include_in_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            
            // Computed attributes
            'type_label' => $this->type_label,
            'status' => $this->status,
            'balance_difference' => $this->current_balance - $this->initial_balance,
            'formatted_current_balance' => number_format($this->current_balance, 2),
            'formatted_initial_balance' => number_format($this->initial_balance, 2),
            
            // Conditional includes
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            
            // Metadata
            'can_edit' => $this->when(
                $request->user() && $request->user()->id === $this->user_id,
                true
            ),
            'can_delete' => $this->when(
                $request->user() && $request->user()->id === $this->user_id && !$this->transactions()->exists(),
                true
            ),
            'transactions_count' => $this->whenCounted('transactions')
        ];
    }
}