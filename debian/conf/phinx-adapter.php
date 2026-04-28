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

include_once '/usr/share/php/EaseFluentPDO/autoload.php';

\Ease\Shared::init(['DB_CONNECTION'], '/etc/multiflexi/database.env', false);

$prefix = '/usr/lib/multiflexi-database/';

$sqlOptions = [];

if (strstr(\Ease\Shared::cfg('DB_CONNECTION'), 'sqlite')) {
    $sqlOptions['database'] = '/var/lib/dbconfig-common/sqlite3/multiflexi/'.basename(\Ease\Shared::cfg('DB_DATABASE'));
}

$engine = new \Ease\SQL\Engine(null, $sqlOptions);
$cfg = [
    'paths' => [
        'migrations' => [$prefix.'migrations'],
        'seeds' => [$prefix.'seeds'],
    ],
    'environments' => [
        'default_environment' => 'production',
        'production' => [
            'adapter' => \Ease\Shared::cfg('DB_CONNECTION'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions),
        ],
    ],
];

return $cfg;
