<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'type' => $this->account->type
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type
            ],
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date->format('Y-m-d'),
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'is_recurring' => $this->is_recurring,
            'recurring_frequency' => $this->recurring_frequency,
            'tags' => $this->tags,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Metadata
            'can_edit' => $this->when(
                $request->user() && $request->user()->id === $this->user_id,
                true
            ),
            'can_delete' => $this->when(
                $request->user() && $request->user()->id === $this->user_id,
                true
            )
        ];
    }
}