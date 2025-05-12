<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Créer un admin (vérifier s'il existe déjà)
        User::updateOrCreate(
            ['email' => 'admin@naturna.ma'], // Conditions pour trouver l'utilisateur
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '0612345678',
                'address' => 'Rabat, Maroc',
            ]
        );

        // Créer des clients (avec des emails uniques)
        for ($i = 1; $i <= 10; $i++) {
            User::updateOrCreate(
                ['email' => "client{$i}@naturna.ma"],
                [
                    'name' => "Client {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'client',
                    'phone' => '06' . str_pad($i, 8, '0', STR_PAD_LEFT),
                    'address' => "Adresse {$i}, Maroc",
                ]
            );
        }
    }
}