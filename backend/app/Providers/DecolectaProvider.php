<?php

namespace App\Providers;

use App\Contracts\PersonProvider;
use App\Data\PersonData;
use App\Services\DecolectaClient;

class DecolectaProvider implements PersonProvider
{
    public function __construct(private DecolectaClient $client) {}

    public function name(): string { return 'decolecta'; }

    public function fetchByDni(string $dni): ?PersonData
    {
        $p = $this->client->fetchByDni($dni);

        if (!is_array($p) || ($p['document_number'] ?? null) !== $dni) {
            return null;
        }

        return new PersonData(
            dni:            $p['document_number'],
            first_lastname: $p['first_last_name'] ?? null,
            second_lastname:$p['second_last_name'] ?? null,
            names:          $p['first_name'] ?? null,
            full_name:      $p['full_name'] ?? null,
            raw:            $p,
            source:         $this->name(),
        );
    }
}
