#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Rah;

require_once __DIR__ . '/vendor/autoload.php';

use App\Command\AppendCommand;
use App\Command\DeleteDeploymentCommand;
use App\Command\DeleteProjectCommand;
use App\Command\UploadCommand;
use App\Command\VersionCheckCommand;
use Symfony\Component\Console\Application;
use function str_starts_with;

$application = new Application();
$application->setName('rah CLI');
$application->setVersion(str_starts_with('@dev_version@', '@dev_version') ? '1337.1337.1337' : '@dev_version@');

$application->add(new AppendCommand());
$application->add(new UploadCommand());
$application->add(new VersionCheckCommand());

$application->run();
