<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $categories = Category::all();
        
        foreach ($users as $user) {
            // Crear 3-5 presupuestos por usuario
            $selectedCategories = $categories->random(rand(3, 5));
            
            foreach ($selectedCategories as $category) {
                Budget::factory()->create([
                    'user_id' => $user->id,
                    'category_id' => $category->id,
                ]);
            }
        }
    }
}