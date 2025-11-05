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

use Phinx\Migration\AbstractMigration;

class Applications extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('apps', ['comment' => 'Available applications that can be deployed and executed in the system']);
        $table
            ->addColumn('enabled', 'boolean', ['default' => false, 'comment' => 'Whether this application is active and can be deployed'])
            ->addColumn('image', 'text', ['null' => true, 'comment' => 'Application icon/logo image data (base64 encoded)'])
            ->addColumn('nazev', 'string', ['null' => true, 'limit' => 32, 'comment' => 'Application name in Czech (nazev = name)'])
            ->addColumn('popis', 'string', ['comment' => 'Application description explaining its purpose and functionality'])
            ->addColumn('executable', 'string', ['comment' => 'Path to executable file or command to run this application'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'Timestamp when application was registered'])
            ->addColumn('DatUpdate', 'datetime', ['null' => true, 'comment' => 'Last modification timestamp'])
            ->addIndex(['nazev'], ['unique' => true])
            ->addIndex(['executable'], ['unique' => true])
            ->create();

        //                if ($this->adapter->getAdapterType() != 'sqlite') {
        //                    $table
        //                        ->changeColumn('id', 'integer', ['identity' => true])
        //                        ->save();
        //                }
    }
}
