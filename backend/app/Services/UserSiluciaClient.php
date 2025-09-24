<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class UserSiluciaClient
{
    // public function fetchPersonalByMeta(string|int $idmeta): array
    // {
    //     $url = 'https://sistemas.regionpuno.gob.pe/siluciav2-api/api/personal_meta';
    //     $res = Http::withoutVerifying()
    //         ->timeout(15)
    //         ->retry(3, 300)             // 3 intentos, 300ms entre intentos
    //         ->get($url, ['idmeta' => $idmeta]);

    //     $data = $res->throw()->json();

    //     // Normaliza a array
    //     return is_array($data) ? $data : [];
    // }
    private const ALLOWED_FILTERS = ['idmeta'];
    public function fetchPersonalByMeta(Request $request): array
    {
        // Base + endpoint
        $base = rtrim(env('SILUCIA_BASE_URL'), '/');
        $url  = $base . '/personal_meta';

        // 1) Toma solo filtros permitidos
        $optional = $request->only(self::ALLOWED_FILTERS);

        // 2) Limpia nulls y strings vacíos
        $query = array_filter(
            array_map(fn($v) => is_string($v) ? trim($v) : $v, $optional),
            fn($v) => $v !== null && $v !== ''
        );

        // 3) Llama correctamente (el bug era pasar ['idmeta' => $query])
        $resp = Http::acceptJson() //por para accpetJson su equivalente withoutVerifying
            // ->withoutVerifying() // úsalo solo si es imprescindible
            ->withoutVerifying()
            ->timeout(15)

            ->retry(3, 300)
            ->get($url, $query)
            ->throw()
            ->json();

        return is_array($resp) ? $resp : [];
    }

    public static function cleanDni(?string $dni): ?string
    {
        if (!$dni) return null;
        $onlyDigits = preg_replace('/\D+/', '', $dni); // quita letras/símbolos
        return $onlyDigits !== '' ? $onlyDigits : null;
    }
}

