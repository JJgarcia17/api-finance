<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando la población de la base de datos...');
        
        // 1. Crear usuario administrador
        $this->command->info('👤 Creando usuario administrador...');
        $this->call(AdminUserSeeder::class);
        
        // 2. Crear usuario de prueba
        $this->command->info('👤 Creando usuario de prueba...');
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        // 3. Crear usuarios adicionales para pruebas
        $this->command->info('👥 Creando usuarios adicionales...');
        User::factory(3)->create();
        
        // 4. Crear categorías predeterminadas
        $this->command->info('📂 Creando categorías...');
        $this->call(CategorySeeder::class);
        
        // 5. Crear cuentas para todos los usuarios
        $this->command->info('🏦 Creando cuentas bancarias...');
        $this->call(AccountSeeder::class);
        
        // 6. Crear transacciones de ejemplo
        $this->command->info('💰 Creando transacciones de ejemplo...');
        $this->call(TransactionSeeder::class);
        
        // 7. Crear presupuestos
        $this->command->info('📊 Creando presupuestos...');
        $this->call(BudgetSeeder::class);
        
        $this->command->info('✅ ¡Base de datos poblada exitosamente!');
        $this->command->info('');
        $this->command->info('📋 Resumen de datos creados:');
        $this->command->info('   • Usuarios: ' . User::count());
        $this->command->info('   • Categorías: ' . \App\Models\Category::count());
        $this->command->info('   • Cuentas: ' . \App\Models\Account::count());
        $this->command->info('   • Transacciones: ' . \App\Models\Transaction::count());
        $this->command->info('   • Presupuestos: ' . \App\Models\Budget::count());
        $this->command->info('');
        $this->command->info('🔑 Credenciales de acceso:');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   Test User: test@example.com / password');
    }
}
