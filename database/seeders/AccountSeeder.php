<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            $existingNames = [];
            
            // Create default accounts for each user
            $bankAccount = Account::factory()->bank()->create([
                'user_id' => $user->id,
                'name' => 'Cuenta Principal',
                'initial_balance' => 10000,
                'current_balance' => 12500
            ]);
            $existingNames[] = $bankAccount->name;

            $creditAccount = Account::factory()->creditCard()->create([
                'user_id' => $user->id,
                'name' => 'Tarjeta de Crédito',
                'initial_balance' => 0,
                'current_balance' => -1500
            ]);
            $existingNames[] = $creditAccount->name;

            $cashAccount = Account::factory()->cash()->create([
                'user_id' => $user->id,
                'name' => 'Efectivo',
                'initial_balance' => 500,
                'current_balance' => 750
            ]);
            $existingNames[] = $cashAccount->name;

            // Create additional random accounts with unique names
            $additionalAccounts = rand(2, 5);
            $attempts = 0;
            $created = 0;
            
            while ($created < $additionalAccounts && $attempts < 20) {
                try {
                    $account = Account::factory()->make(['user_id' => $user->id]);
                    
                    // Ensure unique name for this user
                    $baseName = $account->name;
                    $counter = 1;
                    while (in_array($account->name, $existingNames)) {
                        $account->name = $baseName . ' ' . $counter;
                        $counter++;
                    }
                    
                    $account->save();
                    $existingNames[] = $account->name;
                    $created++;
                } catch (\Exception $e) {
                    // Skip if still duplicate
                }
                $attempts++;
            }
        }
    }
}