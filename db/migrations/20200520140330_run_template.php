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

final class RunTemplate extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Check if the database is MySQL
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // Check if the table already exists
        if (!$this->hasTable('runtemplate')) {
            // Create the runtemplate table - Templates for scheduled application executions
            $table = $this->table('runtemplate', ['id' => false, 'primary_key' => ['id'], 'comment' => 'Templates defining how and when applications should be executed for companies']);
            $table->addColumn('id', 'integer', array_merge(['null' => false, 'identity' => true, 'comment' => 'Primary key'], $unsigned))
                ->addColumn('app_id', 'integer', array_merge(['null' => false, 'comment' => 'Foreign key to apps table - which application to run'], $unsigned))
                ->addColumn('company_id', 'integer', array_merge(['null' => false, 'comment' => 'Foreign key to company table - for which company'], $unsigned))
                ->addColumn('interv', 'string', ['limit' => 1, 'null' => false, 'comment' => 'Interval code for scheduling (d=daily, h=hourly, etc.)'])
                ->addColumn('prepared', 'boolean', ['default' => null, 'null' => true, 'comment' => 'Whether the template is prepared and ready to execute'])
                ->addColumn('success', 'string', ['limit' => 250, 'default' => null, 'null' => true, 'comment' => 'Command or action to execute on successful completion'])
                ->addColumn('fail', 'string', ['limit' => 250, 'default' => null, 'null' => true, 'comment' => 'Command or action to execute on failure'])
                ->addColumn('name', 'string', ['limit' => 250, 'default' => null, 'null' => true, 'comment' => 'Human-readable name for this run template'])
                ->addColumn('delay', 'integer', ['default' => 0, 'null' => false, 'comment' => 'Delay in seconds before job is started after periodic scheduler run'])
                ->addColumn('executor', 'string', ['limit' => 255, 'default' => 'Native', 'null' => false, 'comment' => 'Preferred executor type (Native, Docker, etc.)'])
                ->addIndex(['company_id'], ['name' => 'a2p-company_must_exist'])
                ->addIndex(['app_id', 'company_id'], ['name' => 'app_id', 'type' => 'btree'])
                ->addForeignKey('app_id', 'apps', 'id', ['constraint' => 'a2p-app_must_exist'])
                ->addForeignKey('company_id', 'company', 'id', ['constraint' => 'a2p-company_must_exist'])
                ->create();
        }
    }
}
