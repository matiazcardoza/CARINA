<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_administrador = Role::findByName('SuperAdministrador_pd', 'api');

        $users = User::firstOrCreate(['email' => 'admin@domain.com'], [
            'name' => 'ADMIN',
            'email' => 'admin@domain.com',
            'password' => Hash::make('admin123'),
            'state' => 1,
        ])->assignRole($super_administrador);

        Persona::create([
            'user_id' => $users->id,
            'num_doc' => '75502353',
            'name' => 'ROYER MATIAZ',
            'last_name' => 'HUANCA CARDOZA'
        ]);
    }
}
