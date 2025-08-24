<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\ReniecClient;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    
    // Esta funcion sirve para recuperar un dni de nuestra propia base de datos, y si esta no existe, busca el registro del
    // dni en la base de datos de la reniec, guarda el registro en nuestra propia base de datos y nos entrega el registro
    public function showOrFetch(string $dni, ReniecClient $reniec)
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
        $payload = $reniec->fetchByDni($dni);

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
}
