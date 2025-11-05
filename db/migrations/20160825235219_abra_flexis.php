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

class AbraFlexis extends AbstractMigration
{
    public function change(): void
    {
        // AbraFlexi instances table - Stores connection information for AbraFlexi ERP systems
        $table = $this->table('abraflexis', ['comment' => 'AbraFlexi ERP instances configuration and connection details']);
        $table->addColumn(
            'name',
            'string',
            ['comment' => 'Human-readable name for the AbraFlexi instance'],
        )
            ->addColumn('url', 'string', ['comment' => 'Base URL for AbraFlexi REST API connection endpoint'])
            ->addColumn('user', 'string', ['comment' => 'Username for API authentication to AbraFlexi'])
            ->addColumn('password', 'string', ['comment' => 'Encrypted password for API authentication'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'Record creation timestamp'])
            ->addColumn('DatSave', 'datetime', ['null' => true, 'comment' => 'Last modification timestamp'])
            ->addIndex(['url'], ['unique' => true, 'name' => 'fbs_uniq'])       // Ensure unique API endpoints
            ->create();
    }
}
