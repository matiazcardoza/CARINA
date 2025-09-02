<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DecolectaClient
{
    public function fetchByDni(string $dni): array
    {
        $base = rtrim(config('services.decolecta.base_url', 'https://api.decolecta.com'), '/');
        $url  = $base.'/v1/reniec/dni';

        return Http::withoutVerifying()->timeout(6)->retry(2, 200)
            ->withToken(config('services.decolecta.token'))
            ->get($url, ['numero' => $dni])
            ->throw()
            ->json() ?? [];
    }
}
