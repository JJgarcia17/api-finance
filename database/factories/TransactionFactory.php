<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $types = ['income', 'expense', 'transfer'];
        $type = $this->faker->randomElement($types);
        
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'type' => $type,
            'amount' => $this->faker->randomFloat(2, 1, 10000),
            'description' => $this->faker->sentence(),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'reference_number' => $this->faker->optional()->numerify('REF-####'),
            'notes' => $this->faker->optional()->paragraph(),
            'is_recurring' => $this->faker->boolean(20),
            'recurring_frequency' => $this->faker->optional()->randomElement(['daily', 'weekly', 'monthly', 'yearly']),
            'tags' => $this->faker->optional()->randomElements(['personal', 'business', 'urgent', 'planned'], $this->faker->numberBetween(0, 3))
        ];
    }
}