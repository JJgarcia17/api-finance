<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    private static $accountNames = [
        Account::TYPE_BANK => [
            'Cuenta Corriente BBVA',
            'Cuenta Nómina Santander',
            'Cuenta Ahorro Banorte',
            'Cuenta Principal HSBC',
            'Cuenta Empresarial Banamex'
        ],
        Account::TYPE_CREDIT_CARD => [
            'Tarjeta Visa Gold',
            'Tarjeta Mastercard Platinum',
            'Tarjeta American Express',
            'Tarjeta Departamental',
            'Tarjeta de Crédito Principal'
        ],
        Account::TYPE_CASH => [
            'Efectivo Casa',
            'Efectivo Cartera',
            'Efectivo Oficina',
            'Efectivo Emergencia'
        ],
        Account::TYPE_SAVINGS => [
            'Ahorro Vacaciones',
            'Ahorro Emergencia',
            'Ahorro Inversión',
            'Ahorro Metas'
        ]
    ];

    private static $colors = [
        '#3B82F6', '#EF4444', '#10B981', '#F59E0B',
        '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16',
        '#F97316', '#6366F1', '#14B8A6', '#F43F5E'
    ];

    private static $icons = [
        'credit-card', 'bank', 'wallet', 'piggy-bank',
        'coins', 'dollar-sign', 'euro', 'pound',
        'yen', 'bitcoin', 'chart-line', 'safe'
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(Account::TYPES);
        $names = self::$accountNames[$type] ?? ['Cuenta ' . ucfirst($type)];
        $initialBalance = $this->faker->randomFloat(2, 0, 50000);
        
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement($names),
            'type' => $type,
            'currency' => $this->faker->randomElement(Account::CURRENCIES),
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance + $this->faker->randomFloat(2, -1000, 5000),
            'color' => $this->faker->randomElement(self::$colors),
            'icon' => $this->faker->randomElement(self::$icons),
            'description' => $this->faker->optional(0.3)->sentence(),
            'is_active' => $this->faker->boolean(90),
            'include_in_total' => $this->faker->boolean(85)
        ];
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the account should not be included in total.
     */
    public function excludedFromTotal(): static
    {
        return $this->state(fn (array $attributes) => [
            'include_in_total' => false,
        ]);
    }

    /**
     * Create a bank account.
     */
    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Account::TYPE_BANK,
            'name' => $this->faker->randomElement(self::$accountNames[Account::TYPE_BANK]),
            'icon' => 'bank'
        ]);
    }

    /**
     * Create a credit card account.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Account::TYPE_CREDIT_CARD,
            'name' => $this->faker->randomElement(self::$accountNames[Account::TYPE_CREDIT_CARD]),
            'icon' => 'credit-card',
            'initial_balance' => 0,
            'current_balance' => $this->faker->randomFloat(2, -5000, 0)
        ]);
    }

    /**
     * Create a cash account.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Account::TYPE_CASH,
            'name' => $this->faker->randomElement(self::$accountNames[Account::TYPE_CASH]),
            'icon' => 'wallet'
        ]);
    }
}