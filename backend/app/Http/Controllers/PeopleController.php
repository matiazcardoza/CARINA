<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\DecolectaClient;
use App\Services\PersonFinder;
use App\Services\ReniecClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class PeopleController extends Controller
{
    // public function __construct(private ReniecClient $reniec) {}
    public function __construct(private PersonFinder $finder) {}
    // public function __construct(private DecolectaClient $decolecta) {}
    // Esta funcion sirve para recuperar un dni de nuestra propia base de datos, y si esta no existe, busca el registro del
    // dni en la base de datos de la reniec, guarda el registro en nuestra propia base de datos y nos entrega el registro

    public function showOrFetch(string $dni)
    {
        $dni = str_pad(preg_replace('/\D/', '', $dni), 8, '0', STR_PAD_LEFT);

        if ($person = Person::find($dni)) {
            return response()->json(['from' => 'db', 'data' => $person]);
        }

        // ← AHORA usamos el agregador correctamente
        $data = $this->finder->find($dni);
        if (!$data) {
            return response()->json([
                'ok' => false,
                'message' => 'No encontrado en proveedores externos',
            ], 404);
        }

        $ubg = explode('/', (string)$data->ubigeo);

        $person = Person::create([
            'dni'             => $data->dni,
            'first_lastname'  => $data->first_lastname,
            'second_lastname' => $data->second_lastname,
            'names'           => $data->names,
            'full_name'       => $data->full_name,
            'civil_status'    => $data->civil_status,
            'address'         => $data->address,
            'ubigeo'          => $data->ubigeo,
            'ubg_department'  => $ubg[0] ?? null,
            'ubg_province'    => $ubg[1] ?? null,
            'ubg_district'    => $ubg[2] ?? null,
            'photo_base64'    => $data->photo_base64,
            // OJO: tu tabla no tiene 'raw', lo dejé fuera para que no falle.
            'reniec_consulted_at' => now(), // si quieres, luego generalízalo
        ]);

        return response()->json(['from' => $data->source, 'data' => $person], 201);
    }
}
