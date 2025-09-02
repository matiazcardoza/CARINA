<?php

namespace App\Providers;

use App\Contracts\PersonProvider;
use App\Data\PersonData;
use App\Services\ReniecClient;

class ReniecProvider implements PersonProvider
{
    public function __construct(private ReniecClient $client) {}

    public function name(): string { return 'reniec'; }

    public function fetchByDni(string $dni): ?PersonData
    {
        $payload = $this->client->fetchByDni($dni);
        $ret = data_get($payload, 'consultarResponse.return');

        if (!is_array($ret) || data_get($ret, 'coResultado') !== '0000') {
            return null;
        }

        $dp = data_get($ret, 'datosPersona', []);
        $first  = $dp['apPrimer']    ?? null;
        $second = $dp['apSegundo']   ?? null;
        $names  = $dp['prenombres']  ?? null;
        $full   = trim(($names ?: '').' '.($first ?: '').' '.($second ?: ''));

        return new PersonData(
            dni:            $dni,
            first_lastname: $first,
            second_lastname:$second,
            names:          $names,
            full_name:      $full ?: null,
            civil_status:   $dp['estadoCivil'] ?? null,
            address:        $dp['direccion']   ?? null,
            ubigeo:         $dp['ubigeo']      ?? null,
            photo_base64:   $dp['foto']        ?? null,
            raw:            $payload,
            source:         $this->name(),
        );
    }
}
