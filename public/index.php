<?php

use App\Kernel;

if(!defined('RAH_HOSTNAME')) {
    define('RAH_HOSTNAME', $_SERVER['RAH_HOSTNAME']);
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
