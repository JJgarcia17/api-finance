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
            // Create default accounts for each user
            Account::factory()->bank()->create([
                'user_id' => $user->id,
                'name' => 'Cuenta Principal',
                'initial_balance' => 10000,
                'current_balance' => 12500
            ]);

            Account::factory()->creditCard()->create([
                'user_id' => $user->id,
                'name' => 'Tarjeta de Crédito',
                'initial_balance' => 0,
                'current_balance' => -1500
            ]);

            Account::factory()->cash()->create([
                'user_id' => $user->id,
                'name' => 'Efectivo',
                'initial_balance' => 500,
                'current_balance' => 750
            ]);

            // Create additional random accounts
            Account::factory(rand(2, 5))->create([
                'user_id' => $user->id
            ]);
        }
    }
}