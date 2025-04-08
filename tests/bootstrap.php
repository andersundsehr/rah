<?php

use Symfony\Component\Dotenv\Dotenv;

if(!defined('RAH_HOSTNAME')) {
    define('RAH_HOSTNAME', $_SERVER['RAH_HOSTNAME']);
}

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
