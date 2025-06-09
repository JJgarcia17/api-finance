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
                'description' => 'Ingresos por trabajo dependiente',
                'color' => '#10B981',
                'type' => Category::TYPE_INCOME,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Freelance',
                'description' => 'Ingresos por trabajo independiente',
                'color' => '#3B82F6',
                'type' => Category::TYPE_INCOME,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Inversiones',
                'description' => 'Dividendos, intereses y ganancias de capital',
                'color' => '#8B5CF6',
                'type' => Category::TYPE_INCOME,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Alquiler',
                'description' => 'Ingresos por propiedades en alquiler',
                'color' => '#F59E0B',
                'type' => Category::TYPE_INCOME,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Bonos',
                'description' => 'Bonificaciones y premios',
                'color' => '#EF4444',
                'type' => Category::TYPE_INCOME,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Ventas',
                'description' => 'Ingresos por venta de productos o servicios',
                'color' => '#06B6D4',
                'type' => Category::TYPE_INCOME,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Otros Ingresos',
                'description' => 'Ingresos diversos no categorizados',
                'color' => '#84CC16',
                'type' => Category::TYPE_INCOME,
                'is_active' => true,
                'user_id' => $user->id,
            ],
        ];

        // Categorías de Gastos predeterminadas
        $expenseCategories = [
            [
                'name' => 'Alimentación',
                'description' => 'Supermercado, restaurantes y comida',
                'color' => '#DC2626',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Vivienda',
                'description' => 'Alquiler, hipoteca, servicios básicos',
                'color' => '#7C2D12',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Transporte',
                'description' => 'Combustible, transporte público, mantenimiento',
                'color' => '#1E40AF',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Salud',
                'description' => 'Médicos, medicamentos, seguros de salud',
                'color' => '#059669',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Educación',
                'description' => 'Cursos, libros, materiales educativos',
                'color' => '#7C3AED',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Entretenimiento',
                'description' => 'Cine, streaming, hobbies, deportes',
                'color' => '#DB2777',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Ropa',
                'description' => 'Vestimenta y accesorios',
                'color' => '#9333EA',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Tecnología',
                'description' => 'Dispositivos, software, internet',
                'color' => '#0891B2',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Servicios Financieros',
                'description' => 'Comisiones bancarias, seguros',
                'color' => '#B45309',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Impuestos',
                'description' => 'Impuestos y obligaciones fiscales',
                'color' => '#991B1B',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Ahorros',
                'description' => 'Transferencias a cuentas de ahorro',
                'color' => '#065F46',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Regalos',
                'description' => 'Obsequios y donaciones',
                'color' => '#BE185D',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Viajes',
                'description' => 'Vacaciones y viajes de placer',
                'color' => '#0369A1',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Mascotas',
                'description' => 'Cuidado y alimentación de mascotas',
                'color' => '#A3A3A3',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Otros Gastos',
                'description' => 'Gastos diversos no categorizados',
                'color' => '#6B7280',
                'type' => Category::TYPE_EXPENSE,
                'is_active' => true,
                'user_id' => $user->id,
            ],
        ];

        // Insertar categorías usando transacciones
        DB::transaction(function () use ($incomeCategories, $expenseCategories) {
            // Insertar categorías de ingresos
            foreach ($incomeCategories as $category) {
                Category::create($category);
            }

            // Insertar categorías de gastos
            foreach ($expenseCategories as $category) {
                Category::create($category);
            }
        });

        $this->command->info('✅ Categorías creadas exitosamente:');
    }
}
