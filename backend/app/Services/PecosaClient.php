<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class PecosaClient
{
// Http::withoutVerifying()->get($url);
    // https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordencompradetallado?page=1&per_page=20
    public function indexx(Request $request){
        $endpoint = '/pecosadetallado';
        $base = config('services.silucia.base_url', env('SILUCIA_BASE_URL')) . $endpoint;
        $optional = $request->only([
            'page', 'per_page', 'numero',
            'anio', 'item','desmeta',
            'siaf', 'ruc', 'rsocial',
            'email'

        ]); 
        // $query = array_filter(array_merge($required, $optional), fn($v) => $v !== null && $v !== '');
        $query = array_filter($optional, fn($v) => $v !== null && $v !== '');
        $resp = Http::withoutVerifying()->retry(2, 200)->get($base, $query)->throw()->json();
        return $resp ?? [];
    }

    public function index(Request|array $input){
        $endpoint = '/pecosadetallado';
        $base = config('services.silucia.base_url', env('SILUCIA_BASE_URL')) . $endpoint;

        $optional = is_array($input) ? $input : $input->only([
            'page','per_page','numero','anio','item','desmeta','siaf','ruc','rsocial','email','cod_meta' // ← agregado
        ]);

        $query = array_filter($optional, fn($v) => $v !== null && $v !== '');
        $resp = Http::withoutVerifying()->retry(2, 200)->get($base, $query)->throw()->json();
        return $resp ?? [];
    }
}