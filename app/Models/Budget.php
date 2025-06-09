<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'period',
        'start_date',
        'end_date',
        'is_active',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Scopes siguiendo el patrón existente
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->when($categoryId, fn($q) => $q->where('category_id', $categoryId));
    }

    public function scopeActive($query, $isActive = null)
    {
        return $query->when($isActive !== null, fn($q) => $q->where('is_active', $isActive));
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->when($period, fn($q) => $q->where('period', $period));
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
            return $q->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            });
        });
    }
}
