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
    public function showOrFetchx(string $dni)
    {
        // normaliza el valor ingresado en la variable $dni, para que solo haya numeros y pone ceros cuando falta digitos
        $dni = str_pad(preg_replace('/\D/','', $dni), 8, '0', STR_PAD_LEFT);
        
        // este es un ejemplo de lo que responde si la respuesta es exitosa:
        // {
        //     "from": "db",
        //     "data": {
        //         "dni": "12345678",
        //         "first_name": "Carlos",
        //         "last_name": "Ramírez",
        //         "birth_date": "1990-05-12",
        //         "gender": "M",
        //         "address": "Av. Perú 123",
        //         "created_at": "2025-08-23T14:22:10.000000Z",
        //         "updated_at": "2025-08-23T14:22:10.000000Z"
        //     }
        // }
        $person = Person::find($dni);
        if ($person) {
            return response()->json(['from' => 'db', 'data' => $person]);
        }

        // Consultar RENIEC
        // $payload = $this->reniec->fetchByDni($dni);
        // $payload = $this->reniec->fetchByDni($dni);
        $payload = $this->finder->find($dni);

        // accedemos a la respuesta con notacion punto, para eso sirve la funcion data_get
        $ret = data_get($payload, 'consultarResponse.return');

        // la respuesta del servicio de reniec devulve un codigo, y estamos suponiendo a partir de una sola respuesta, que el 
        // codigo 0000 se da en casos existosos
        if (data_get($ret, 'coResultado') !== '0000') {
            return response()->json([
                'ok' => false,
                'message' => data_get($ret, 'deResultado', 'No encontrado')
            ], 404);
        }

        $dp = data_get($ret, 'datosPersona', []);
        // particiona el ubigeo (informacion qu indica donde vive la persona)
        $ubg = explode('/', (string)($dp['ubigeo'] ?? ''));

        $person = Person::create([
            'dni'            => $dni,
            'first_lastname' => $dp['apPrimer']    ?? null,
            'second_lastname'=> $dp['apSegundo']   ?? null,
            'names'          => $dp['prenombres']  ?? null,
            'full_name'      => trim(($dp['prenombres'] ?? '').' '.($dp['apPrimer'] ?? '').' '.($dp['apSegundo'] ?? '')),
            'civil_status'   => $dp['estadoCivil'] ?? null,
            'address'        => $dp['direccion']   ?? null,
            'ubigeo'         => $dp['ubigeo']      ?? null,
            'ubg_department' => $ubg[0] ?? null,
            'ubg_province'   => $ubg[1] ?? null,
            'ubg_district'   => $ubg[2] ?? null,
            'photo_base64'   => $dp['foto']        ?? null,
            'raw'            => $payload,
            'reniec_consulted_at' => now(),
        ]);

        return response()->json(['from' => 'reniec', 'data' => $person], 201);
    }


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
    public function showOrFetchz(string $dni)
    {
        // Normaliza DNI a 8 dígitos
        $dni = str_pad(preg_replace('/\D/', '', $dni), 8, '0', STR_PAD_LEFT);
        if (strlen($dni) !== 8) {
            return response()->json(['ok' => false, 'message' => 'DNI inválido'], 422);
        }

        // 1) DB primero
        if ($person = Person::find($dni)) {
            return response()->json(['from' => 'db', 'data' => $person]);
        }

        // 2) Consultar Decolecta
        // try {
            $p = $this->decolecta->fetchByDni($dni);
            Log::info($p);
        // } catch (\Throwable $e) {
        //     report($e);
        //     return response()->json(['ok' => false, 'message' => 'Error consultando Decolecta'], 502);
        // }

        // 3) Validar respuesta
        if (!is_array($p) || ($p['document_number'] ?? null) !== $dni) {
            return response()->json(['ok' => false, 'message' => 'No encontrado en Decolecta'], 404);
        }

        // 4) Guardar normalizado (tu tabla no tiene 'raw', por eso no lo guardo)
        $full = $p['full_name'] ?? trim(
            ($p['first_name'] ?? '').' '.
            ($p['first_last_name'] ?? '').' '.
            ($p['second_last_name'] ?? '')
        );

        $person = Person::create([
            'dni'             => $dni,
            'first_lastname'  => $p['first_last_name']  ?? null,
            'second_lastname' => $p['second_last_name'] ?? null,
            'names'           => $p['first_name']       ?? null,
            'full_name'       => $full ?: null,
            'civil_status'    => null,
            'address'         => null,
            'ubigeo'          => null,
            'ubg_department'  => null,
            'ubg_province'    => null,
            'ubg_district'    => null,
            'photo_base64'    => null,
            'reniec_consulted_at' => now(), // si quieres luego renómbralo a external_consulted_at
        ]);

        return response()->json(['from' => 'decolecta', 'data' => $person], 201);
    }
}
