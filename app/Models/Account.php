<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'currency',
        'initial_balance',
        'current_balance',
        'color',
        'icon',
        'description',
        'is_active',
        'include_in_total'
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'include_in_total' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Account types
    const TYPE_BANK = 'bank';
    const TYPE_CREDIT_CARD = 'credit_card';
    const TYPE_CASH = 'cash';
    const TYPE_SAVINGS = 'savings';
    const TYPE_INVESTMENT = 'investment';
    const TYPE_LOAN = 'loan';
    const TYPE_OTHER = 'other';

    const TYPES = [
        self::TYPE_BANK,
        self::TYPE_CREDIT_CARD,
        self::TYPE_CASH,
        self::TYPE_SAVINGS,
        self::TYPE_INVESTMENT,
        self::TYPE_LOAN,
        self::TYPE_OTHER
    ];

    // Currencies
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_MXN = 'MXN';
    const CURRENCY_GBP = 'GBP';
    const CURRENCY_CAD = 'CAD';

    const CURRENCIES = [
        self::CURRENCY_USD,
        self::CURRENCY_EUR,
        self::CURRENCY_MXN,
        self::CURRENCY_GBP,
        self::CURRENCY_CAD
    ];

    /**
     * Scope to filter accounts by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter accounts included in total
     */
    public function scopeIncludedInTotal($query)
    {
        return $query->where('include_in_total', true);
    }

    /**
     * Get the user that owns the account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this account
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_BANK => 'Cuenta Bancaria',
            self::TYPE_CREDIT_CARD => 'Tarjeta de Crédito',
            self::TYPE_CASH => 'Efectivo',
            self::TYPE_SAVINGS => 'Cuenta de Ahorros',
            self::TYPE_INVESTMENT => 'Inversión',
            self::TYPE_LOAN => 'Préstamo',
            self::TYPE_OTHER => 'Otro',
            default => 'Desconocido'
        };
    }

    /**
     * Get the status label
     */
    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'Activa' : 'Inactiva';
    }

    /**
     * Route model binding
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
                   ->where('user_id', auth('sanctum')->id())
                   ->firstOrFail();
    }
}