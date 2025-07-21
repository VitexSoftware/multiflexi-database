<?php

declare(strict_types=1);

/**
 * This file is part of the MultiFlexi package
 *
 * https://multiflexi.eu/
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (file_exists('./vendor/autoload.php')) {
    include_once './vendor/autoload.php';
} else {
    include_once '../vendor/autoload.php';
}

$shared = \Ease\Shared::instanced();

if (file_exists('../.env')) {
    $shared->loadConfig('../.env', true);
}

$prefix = file_exists('./db/') ? './db/' : '../db/';

$sqlOptions = [];

if (strstr(\Ease\Shared::cfg('DB_CONNECTION'), 'sqlite')) {
    $sqlOptions['database'] = $prefix.basename(\Ease\Shared::cfg('DB_DATABASE'));
}

$engine = new \Ease\SQL\Engine(null, $sqlOptions);
$cfg = [
    'paths' => [
        'migrations' => [$prefix.'migrations'],
        'seeds' => [$prefix.'seeds'],
    ],
    'environments' => [
        'default_environment' => 'production',
        'development' => [
            'adapter' => \Ease\Shared::cfg('DB_CONNECTION'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions),
        ],
        'production' => [
            'adapter' => \Ease\Shared::cfg('DB_CONNECTION'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions),
        ],
    ],
];

return $cfg;
