<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class StorageUsage
{
    public function __construct(
        public Size $limit,
        public Size $used,
        public Size $free,
        public Size $availableDisk,
        public bool $diskIsFullWarning,
        /**
         * @var float SI value 1.0 ^= 100%
         */
        public float $percent,
        public string $percentHuman,
    ) {
    }
}
