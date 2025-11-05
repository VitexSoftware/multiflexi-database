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

class ConfigRegistry extends AbstractMigration
{
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $customFields = $this->table('conffield', ['comment' => 'Configuration field definitions for applications - defines what configuration options each app has']);
        $customFields->addColumn('app_id', 'integer', array_merge(['null' => false, 'comment' => 'Foreign key to apps table'], $unsigned))
            ->addColumn('keyname', 'string', ['length' => 64, 'comment' => 'Configuration key name (e.g., database_host, api_key)'])
            ->addColumn('type', 'string', ['length' => 32, 'comment' => 'Configuration value type (string, integer, boolean, etc.)'])
            ->addColumn('description', 'string', ['length' => 1024, 'comment' => 'Human-readable description of what this configuration option does'])
            ->addIndex(['app_id', 'keyname'], ['unique' => true])
            ->addForeignKey('app_id', 'apps', 'id', ['constraint' => 'cff-app_must_exist', 'delete' => 'CASCADE']);
        $customFields->create();

        $configs = $this->table('configuration', ['comment' => 'Actual configuration values for applications per company and run template']);
        $configs->addColumn('app_id', 'integer', array_merge(['null' => false, 'comment' => 'Foreign key to apps table'], $unsigned))
            ->addColumn('company_id', 'integer', array_merge(['null' => false, 'comment' => 'Foreign key to company table'], $unsigned))
            ->addColumn('key', 'string', ['length' => 64, 'comment' => 'Configuration key name matching conffield.keyname'])
            ->addColumn('value', 'string', ['length' => 1024, 'comment' => 'Configuration value for this key'])
            ->addColumn('runtemplate_id', 'integer', array_merge(['null' => false, 'comment' => 'Foreign key to runtemplate table'], $unsigned))
            ->addIndex(['app_id', 'company_id'])
            ->addIndex(['runtemplate_id', 'key'], ['unique' => true])
            ->addForeignKey('app_id', 'apps', ['id'], ['constraint' => 'cfg-app_must_exist', 'delete' => 'CASCADE'])
            ->addForeignKey('company_id', 'company', ['id'], ['constraint' => 'cfg-company_must_exist', 'delete' => 'CASCADE']);
        $configs->create();
    }
}
