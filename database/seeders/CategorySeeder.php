<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el usuario de prueba o crear uno si no existe
        $user = User::where('email', 'test@example.com')->first();
        
        if (!$user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Categorías de Ingresos predeterminadas
        $incomeCategories = [
            [
                'name' => 'Salario',
                'color' => '#10B981',
                'icon' => 'briefcase',
                'type' => 'income',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Freelance',
                'color' => '#3B82F6',
                'icon' => 'laptop',
                'type' => 'income',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Inversiones',
                'color' => '#8B5CF6',
                'icon' => 'trending-up',
                'type' => 'income',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Alquiler',
                'color' => '#F59E0B',
                'icon' => 'home',
                'type' => 'income',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Bonos',
                'color' => '#EF4444',
                'icon' => 'gift',
                'type' => 'income',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Ventas',
                'color' => '#06B6D4',
                'icon' => 'shopping-cart',
                'type' => 'income',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Otros Ingresos',
                'color' => '#84CC16',
                'icon' => 'plus-circle',
                'type' => 'income',
                'is_active' => true,
                'user_id' => $user->id,
            ],
        ];

        // Categorías de Gastos predeterminadas
        $expenseCategories = [
            [
                'name' => 'Alimentación',
                'color' => '#F97316',
                'icon' => 'utensils',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Transporte',
                'color' => '#3B82F6',
                'icon' => 'car',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Vivienda',
                'color' => '#8B5CF6',
                'icon' => 'home',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Entretenimiento',
                'color' => '#EC4899',
                'icon' => 'film',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Salud',
                'color' => '#10B981',
                'icon' => 'heart',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Educación',
                'color' => '#F59E0B',
                'icon' => 'book-open',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Ropa',
                'color' => '#EF4444',
                'icon' => 'shopping-bag',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Servicios',
                'color' => '#06B6D4',
                'icon' => 'cog',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Tecnología',
                'color' => '#6366F1',
                'icon' => 'desktop-computer',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Otros Gastos',
                'color' => '#6B7280',
                'icon' => 'dots-horizontal',
                'type' => 'expense',
                'is_active' => true,
                'user_id' => $user->id,
            ],
        ];

        // Insertar categorías usando transacción para mejor rendimiento
        DB::transaction(function () use ($incomeCategories, $expenseCategories) {
            // Insertar categorías de ingresos
            foreach ($incomeCategories as $category) {
                Category::firstOrCreate(
                    [
                        'user_id' => $category['user_id'],
                        'name' => $category['name'],
                        'type' => $category['type']
                    ],
                    $category
                );
            }

            // Insertar categorías de gastos
            foreach ($expenseCategories as $category) {
                Category::firstOrCreate(
                    [
                        'user_id' => $category['user_id'],
                        'name' => $category['name'],
                        'type' => $category['type']
                    ],
                    $category
                );
            }
        });

        // Crear categorías adicionales para otros usuarios
        $otherUsers = User::where('email', '!=', 'test@example.com')->get();
        
        foreach ($otherUsers as $otherUser) {
            // Crear algunas categorías básicas para cada usuario
            $basicCategories = [
                ['name' => 'Salario', 'type' => 'income', 'color' => '#10B981', 'icon' => 'briefcase'],
                ['name' => 'Alimentación', 'type' => 'expense', 'color' => '#F97316', 'icon' => 'utensils'],
                ['name' => 'Transporte', 'type' => 'expense', 'color' => '#3B82F6', 'icon' => 'car'],
                ['name' => 'Entretenimiento', 'type' => 'expense', 'color' => '#EC4899', 'icon' => 'film'],
            ];

            foreach ($basicCategories as $category) {
                Category::firstOrCreate(
                    [
                        'user_id' => $otherUser->id,
                        'name' => $category['name'],
                        'type' => $category['type']
                    ],
                    array_merge($category, [
                        'user_id' => $otherUser->id,
                        'is_active' => true
                    ])
                );
            }
        }

        $this->command->info('✅ Categorías creadas exitosamente');
    }
}
