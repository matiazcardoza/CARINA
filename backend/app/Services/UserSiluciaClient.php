<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class UserSiluciaClient
{
    public function fetchPersonalByMeta(string|int $idmeta): array
    {
        $url = 'https://sistemas.regionpuno.gob.pe/siluciav2-api/api/personal_meta';
        $res = Http::withoutVerifying()
            ->timeout(15)
            ->retry(3, 300)             // 3 intentos, 300ms entre intentos
            ->get($url, ['idmeta' => $idmeta]);

        $data = $res->throw()->json();

        // Normaliza a array
        return is_array($data) ? $data : [];
    }

    public static function cleanDni(?string $dni): ?string
    {
        if (!$dni) return null;
        $onlyDigits = preg_replace('/\D+/', '', $dni); // quita letras/símbolos
        return $onlyDigits !== '' ? $onlyDigits : null;
    }
}