<?php

declare(strict_types=1);

$minimumVersion = '8.5.12';

// @phpstan-ignore if.alwaysFalse (This standalone gate must also reject PHP versions below Composer's minimum.)
if (version_compare(PHP_VERSION, $minimumVersion, '<')) {
    echo sprintf("PHP %s or newer is required; running PHP %s.\n", $minimumVersion, PHP_VERSION);
    exit(1);
}

echo sprintf("PHP %s satisfies the minimum version %s.\n", PHP_VERSION, $minimumVersion);
