<?php

declare(strict_types=1);

namespace App\Auth;

final readonly class AuthResult
{
    public function __construct(
        public bool $allowedToCall,
        public string $reason,
    ) {
    }
}
