<?php

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;

$bundles = [
    FrameworkBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    MakerBundle::class => ['dev' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
];

if (!class_exists(MakerBundle::class)) {
    unset($bundles[MakerBundle::class]);
}

return $bundles;
