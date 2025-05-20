<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class NamedObject
{
    public function __construct(
        public string $name,
    ) {
    }
}
