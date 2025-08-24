<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class ReniecClient
{
    public function fetchByDni(string $dni): array
    {
        $resp = Http::retry(2, 200)
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
