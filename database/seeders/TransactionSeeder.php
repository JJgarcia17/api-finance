<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Obtener usuarios, cuentas y categorías existentes
            $users = User::all();
            $accounts = Account::all();
            $categories = Category::all();

            if ($users->isEmpty() || $accounts->isEmpty() || $categories->isEmpty()) {
                $this->command->warn('No hay usuarios, cuentas o categorías disponibles para crear transacciones.');
                return;
            }

            // Crear transacciones para cada usuario
            foreach ($users as $user) {
                $userAccounts = $accounts->where('user_id', $user->id);
                $userCategories = $categories->where('user_id', $user->id);

                if ($userAccounts->isEmpty() || $userCategories->isEmpty()) {
                    continue;
                }

                // Crear 20-50 transacciones por usuario
                $transactionCount = rand(20, 50);
                
                for ($i = 0; $i < $transactionCount; $i++) {
                    $account = $userAccounts->random();
                    $category = $userCategories->random();
                    
                    // Determinar tipo basado en la categoría
                    $type = $category->type === 'income' ? 'income' : 'expense';
                    
                    Transaction::create([
                        'user_id' => $user->id,
                        'account_id' => $account->id,
                        'category_id' => $category->id,
                        'type' => $type,
                        'amount' => $this->generateAmount($type),
                        'description' => $this->generateDescription($type, $category->name),
                        'transaction_date' => $this->generateDate(),
                        'reference_number' => $this->generateReference(),
                        'notes' => rand(0, 1) ? $this->generateNotes() : null,
                        'is_recurring' => rand(0, 100) < 15, // 15% probabilidad
                        'recurring_frequency' => rand(0, 100) < 15 ? $this->generateFrequency() : null,
                        'tags' => rand(0, 1) ? $this->generateTags() : null
                    ]);
                }
            }

            $this->command->info('Transacciones creadas exitosamente.');
        });
    }

    private function generateAmount(string $type): float
    {
        if ($type === 'income') {
            // Ingresos: entre 500 y 5000
            return round(rand(50000, 500000) / 100, 2);
        } else {
            // Gastos: entre 10 y 2000
            return round(rand(1000, 200000) / 100, 2);
        }
    }

    private function generateDescription(string $type, string $categoryName): string
    {
        $incomeDescriptions = [
            'Salario mensual',
            'Pago de freelance',
            'Venta de producto',
            'Comisión por ventas',
            'Ingreso adicional',
            'Pago de servicios',
            'Reembolso',
            'Dividendos'
        ];

        $expenseDescriptions = [
            'Compra en ' . $categoryName,
            'Pago de ' . $categoryName,
            'Gasto en ' . $categoryName,
            'Factura de ' . $categoryName,
            'Compra mensual',
            'Pago de servicios',
            'Compra necesaria',
            'Gasto imprevisto'
        ];

        return $type === 'income' 
            ? $incomeDescriptions[array_rand($incomeDescriptions)]
            : $expenseDescriptions[array_rand($expenseDescriptions)];
    }

    private function generateDate(): string
    {
        // Fechas entre hace 6 meses y hoy
        $start = strtotime('-6 months');
        $end = time();
        $randomTimestamp = rand($start, $end);
        
        return date('Y-m-d', $randomTimestamp);
    }

    private function generateReference(): ?string
    {
        if (rand(0, 100) < 30) { // 30% probabilidad
            return 'REF-' . strtoupper(substr(md5(uniqid()), 0, 8));
        }
        return null;
    }

    private function generateNotes(): string
    {
        $notes = [
            'Transacción programada',
            'Pago realizado a tiempo',
            'Compra necesaria para el hogar',
            'Gasto planificado',
            'Inversión a largo plazo',
            'Compra de emergencia',
            'Pago de deuda',
            'Ahorro mensual'
        ];
        
        return $notes[array_rand($notes)];
    }

    private function generateFrequency(): string
    {
        $frequencies = ['monthly', 'weekly', 'yearly', 'daily'];
        return $frequencies[array_rand($frequencies)];
    }

    private function generateTags(): array
    {
        $allTags = ['personal', 'business', 'urgent', 'planned', 'recurring', 'one-time', 'important', 'optional'];
        $numTags = rand(1, 3);
        
        return array_slice($allTags, 0, $numTags);
    }
}