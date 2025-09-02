<?php

namespace App\Data;

final class PersonData
{
    public function __construct(
        public string $dni,
        public ?string $first_lastname = null,
        public ?string $second_lastname = null,
        public ?string $names = null,
        public ?string $full_name = null,
        public ?string $civil_status = null,
        public ?string $address = null,
        public ?string $ubigeo = null,
        public ?string $photo_base64 = null,
        public array $raw = [],
        public string $source = '', // 'reniec' | 'decolecta' | ...
    ) {}
}
