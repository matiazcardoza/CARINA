<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addTurnos extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('shifts')->insert([
            [
                'name' => 'MAÑANA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'TARDE',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
