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

include_once '/usr/share/php/EaseCore/Atom.php';

include_once '/usr/share/php/EaseCore/Shared.php';

include_once '/usr/share/php/EaseCore/Molecule.php';

include_once '/usr/share/php/EaseCore/Logger/Logging.php';

include_once '/usr/share/php/EaseCore/Sand.php';

include_once '/usr/share/php/EaseCore/Functions.php';

include_once '/usr/share/php/EaseCore/Logger/Message.php';

include_once '/usr/share/php/EaseCore/Logger/Loggingable.php';

include_once '/usr/share/php/EaseCore/Logger/Loggingable.php';

include_once '/usr/share/php/EaseCore/Logger/ToMemory.php';

include_once '/usr/share/php/EaseCore/recordkey.php';

include_once '/usr/share/php/EaseCore/Brick.php';

include_once '/usr/share/php/EaseCore/Person.php';

include_once '/usr/share/php/EaseCore/Anonym.php';

include_once '/usr/share/php/EaseCore/User.php';

include_once '/usr/share/php/EaseCore/Logger/ToStd.php';

include_once '/usr/share/php/EaseCore/Logger/ToSyslog.php';

include_once '/usr/share/php/EaseCore/Logger/ToConsole.php';

include_once '/usr/share/php/EaseCore/Logger/Regent.php';

include_once '/usr/share/php/EaseCore/Logger/ToMemory.php';

include_once '/usr/share/php/EaseCore/Exception.php';

include_once '/usr/share/php/EaseFluentPDO/Orm.php';

include_once '/usr/share/php/EaseFluentPDO/Engine.php';

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
