<?php

namespace App\Http\Resources\Transfer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
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
            'reference_number' => $this->reference_number,
            'amount' => number_format($this->amount, 2, '.', ''),
            'description' => $this->description,
            'transfer_date' => $this->transaction_date->format('Y-m-d'),
            'transfer_datetime' => $this->transaction_date->format('Y-m-d H:i:s'),
            'status' => 'completed', // Since we use the existing transaction system
            'from_account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'type' => $this->account->type,
                'currency' => $this->account->currency,
            ],
            'to_account' => [
                'id' => $this->destinationAccount->id,
                'name' => $this->destinationAccount->name,
                'type' => $this->destinationAccount->type,
                'currency' => $this->destinationAccount->currency,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}