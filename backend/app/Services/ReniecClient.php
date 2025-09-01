<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class ReniecClient
{
    // https://ws2.pide.gob.pe/Rest/RENIEC/Consultar?nuDniConsulta=12345678&nuDniUsuario=70977554&nuRucUsuario=20123456789&password=TU_PASSWORD&out=json

    public function fetchByDni(string $dni): array
    {
        $resp = Http::withoutVerifying()->retry(2, 200)
            ->get(config('services.reniec.base_url', env('RENIEC_BASE_URL')), [
                'nuDniConsulta' => $dni,
                'nuDniUsuario'  => env('RENIEC_USER_DNI'),
                'nuRucUsuario'  => env('RENIEC_USER_RUC'),
                'password'      => env('RENIEC_PASSWORD'),
                'out'           => 'json'
            ])->throw()->json();

        return $resp ?? [];
    }
}
