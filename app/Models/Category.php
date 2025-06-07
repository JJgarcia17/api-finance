<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'color',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    public static function getTypes(): array
    {
        return [
            self::TYPE_INCOME,
            self::TYPE_EXPENSE,
        ];
    }

    /**
     * Scope para filtrar por usuario autenticado
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Route model binding personalizado para verificar ownership
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
                   ->where('user_id', auth()->id())
                   ->firstOrFail();
    }

    /**
     * Get the user that owns the category.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the type label in Spanish
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_INCOME => 'Ingreso',
            self::TYPE_EXPENSE => 'Gasto',
            default => 'Desconocido'
        };
    }
}