<?php

namespace App\Services;

use App\Contracts\PersonProvider;
use App\Data\PersonData;
use Throwable;

class PersonFinder
{
    /** @param PersonProvider[] $providers */
    public function __construct(private iterable $providers) {}

    public function find(string $dni): ?PersonData
    {
        foreach ($this->providers as $provider) {
            try {
                $data = $provider->fetchByDni($dni);
                if ($data) return $data;
            } catch (Throwable $e) {
                report($e); // no rompas el flujo
            }
        }
        return null;
    }
}
