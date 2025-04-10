<?php

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    FrameworkBundle::class => ['all' => true],
    MakerBundle::class => ['dev' => true],
    TwigBundle::class => ['all' => true],
];
