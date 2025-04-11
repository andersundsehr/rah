<?php

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

if (!class_exists(MakerBundle::class)) {
    return [
        FrameworkBundle::class => ['all' => true],
        TwigBundle::class => ['all' => true],
    ];
}

return [
    FrameworkBundle::class => ['all' => true],
    MakerBundle::class => ['dev' => true],
    TwigBundle::class => ['all' => true],
];
