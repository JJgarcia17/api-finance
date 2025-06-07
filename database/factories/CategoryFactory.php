<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $incomeCategories = [
            'Salario', 'Freelance', 'Inversiones', 'Bonos', 'Ventas', 'Alquiler'
        ];
        
        $expenseCategories = [
            'Alimentación', 'Transporte', 'Vivienda', 'Entretenimiento', 
            'Salud', 'Educación', 'Ropa', 'Servicios', 'Otros'
        ];
        
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', 
            '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'
        ];
        
        $icons = [
            'fas fa-utensils', 'fas fa-car', 'fas fa-home', 'fas fa-gamepad',
            'fas fa-heartbeat', 'fas fa-graduation-cap', 'fas fa-tshirt',
            'fas fa-wifi', 'fas fa-dollar-sign', 'fas fa-briefcase'
        ];
        
        $type = $this->faker->randomElement([Category::TYPE_INCOME, Category::TYPE_EXPENSE]);
        $categories = $type === Category::TYPE_INCOME ? $incomeCategories : $expenseCategories;
        
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->unique()->randomElement($categories) . ' ' . $this->faker->unique()->word,
            'type' => $type,
            'color' => $this->faker->randomElement($colors),
            'icon' => $this->faker->randomElement($icons),
            'is_active' => $this->faker->boolean(90), // 90% activas
        ];
    }
    
    /**
     * Indicate that the category is for income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Category::TYPE_INCOME,
        ]);
    }
    
    /**
     * Indicate that the category is for expenses.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Category::TYPE_EXPENSE,
        ]);
    }
    
    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
