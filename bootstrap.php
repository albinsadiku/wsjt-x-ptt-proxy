<?php

declare(strict_types=1);

use App\Config\AppConfig;
use RuntimeException;

$autoload = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    throw new RuntimeException('Composer dependencies are missing. Run "composer install".');
}

require $autoload;

AppConfig::bootstrap(__DIR__);

