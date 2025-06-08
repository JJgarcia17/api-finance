<?php

namespace App\Http\Resources\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AccountCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'accounts' => $this->collection,
            'summary' => $this->when(
                $this->collection->isNotEmpty(),
                function () {
                    $accounts = $this->collection;
                    
                    return [
                        'total_accounts' => $accounts->count(),
                        'active_accounts' => $accounts->where('is_active', true)->count(),
                        'total_balance' => $accounts->where('include_in_total', true)->sum('current_balance'),
                        'by_type' => $accounts->groupBy('type')->map->count(),
                        'by_currency' => $accounts->groupBy('currency')->map(function ($accounts) {
                            return [
                                'count' => $accounts->count(),
                                'total_balance' => $accounts->where('include_in_total', true)->sum('current_balance')
                            ];
                        })
                    ];
                }
            )
        ];
    }
}