<?php

namespace App\Repositories\Account;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountRepository
{
    /**
     * Get all accounts for a user with optional filtering
     */
    public function getAllForUser(
        int $userId,
        ?string $type = null,
        ?bool $isActive = null,
        ?bool $includeInTotal = null,
        string $sortBy = 'name',
        string $sortDirection = 'asc',
        ?int $perPage = null
    ): Collection|LengthAwarePaginator {
        $query = Account::forUser($userId)
            ->with('user');

        // Apply filters
        if ($type) {
            $query->where('type', $type);
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        if ($includeInTotal !== null) {
            $query->where('include_in_total', $includeInTotal);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Return paginated or all results
        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Find an account for a specific user
     */
    public function findForUser(int $accountId, int $userId): Account
    {
        $account = Account::forUser($userId)
            ->with('user')
            ->find($accountId);

        if (!$account) {
            throw new ModelNotFoundException('Account not found');
        }

        return $account;
    }

    /**
     * Create a new account
     */
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    /**
     * Update an account
     */
    public function update(Account $account, array $data): Account
    {
        $account->update($data);
        return $account->fresh();
    }

    /**
     * Soft delete an account
     */
    public function delete(Account $account): bool
    {
        return $account->delete();
    }

    /**
     * Force delete an account
     */
    public function forceDelete(Account $account): bool
    {
        return $account->forceDelete();
    }

    /**
     * Restore a soft deleted account
     */
    public function restore(Account $account): bool
    {
        return $account->restore();
    }

    /**
     * Toggle account status
     */
    public function toggleStatus(Account $account): Account
    {
        $account->update(['is_active' => !$account->is_active]);
        return $account->fresh();
    }

    /**
     * Check if account name exists for user
     */
    public function nameExistsForUser(string $name, int $userId, ?int $excludeId = null): bool
    {
        $query = Account::forUser($userId)->where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get account count by type for user
     */
    public function getCountByType(int $userId): array
    {
        return Account::forUser($userId)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get account statistics for user
     */
    public function getStatsForUser(int $userId): array
    {
        $accounts = Account::forUser($userId)->get();
        
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

    /**
     * Update account balance
     */
    public function updateBalance(Account $account, float $newBalance): Account
    {
        $account->update(['current_balance' => $newBalance]);
        return $account->fresh();
    }

    /**
     * Get active accounts for user
     */
    public function getActiveForUser(int $userId): Collection
    {
        return Account::forUser($userId)
            ->active()
            ->orderBy('name')
            ->get();
    }
}