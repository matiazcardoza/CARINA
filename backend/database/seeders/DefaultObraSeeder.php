<?php

namespace Database\Seeders;

use App\Models\Obra;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultObraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $default_obra = Obra::firstOrCreate(
                ['idmeta_silucia' => '0000000000'],
                [
                    // 'idmeta_silucia'    => '0000000000',
                    'anio'              => '2000',
                    'nombre' => 'GENERAL',
                    'codmeta' => '0000'
                ]
            );
            setPermissionsTeamId($default_obra->id); 
    }
}
