<?php

namespace App\Contracts;

use App\Data\PersonData;

interface PersonProvider
{
    public function name(): string;

    /** Devuelve PersonData o null si no encontró/no fue exitoso */
    public function fetchByDni(string $dni): ?PersonData;
}
