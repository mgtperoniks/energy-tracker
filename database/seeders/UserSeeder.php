<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Direktur',
                'email' => 'direktur@peroniks.com',
                'password' => Hash::make('peronijayajaya123'),
            ],
            [
                'name' => 'Finance',
                'email' => 'finance@peroniks.com',
                'password' => Hash::make('finance123'),
            ],
            [
                'name' => 'Management Rep',
                'email' => 'mr@peroniks.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Manager HR',
                'email' => 'managerhr@peroniks.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Kabag Maintenance',
                'email' => 'kabagmaintenance@peroniks.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Marketing Export',
                'email' => 'marketingexport@peroniks.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Pajak',
                'email' => 'pajak@peroniks.com',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $user['password']
                ]
            );
        }
    }
}
