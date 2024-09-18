<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Admin',                //1
            'HR-Manager',           //2
            'Operation-Manager',    //3
            'Sales-Manager',        //4
            'Client',               //5
            'Accountant',           //6
        ];

        // Loop through and create or update each role
        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role]);
        }

        // Create a default admin user
        $admin = User::updateOrCreate(
            ['role_id' => 1],
            [
                'email' => 'admin@gmail.com',
                'name' => 'Default Admin',
                'password' => Hash::make('12345678'), // Set your default password
            ]
        );


    }
}
