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

final class TaskTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('task')) {
            return;
        }

        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table('task');
        $table
            ->addColumn('runtemplate_id', 'integer', array_merge(['null' => false, 'comment' => 'FK to runtemplate'], $unsigned))
            ->addColumn('window_start', 'datetime', ['null' => false, 'comment' => 'Start of the scheduling window'])
            ->addColumn('window_end', 'datetime', ['null' => false, 'comment' => 'End of the scheduling window (= next interval start)'])
            ->addColumn('deadline', 'datetime', ['null' => false, 'comment' => 'Deadline by which the result must be ready (default = window_end)'])
            ->addColumn('state', 'string', [
                'length' => 16,
                'null' => false,
                'default' => 'open',
                'comment' => 'Task state: open | running | fulfilled | fulfilled_late | failed | missed',
            ])
            ->addColumn('fulfilled_by_job_id', 'integer', array_merge(['null' => true, 'default' => null, 'comment' => 'Job that fulfilled this task'], $unsigned))
            ->addColumn('fulfilled_at', 'datetime', ['null' => true, 'default' => null, 'comment' => 'When the task was fulfilled'])
            ->addColumn('attempts', 'integer', array_merge(['null' => false, 'default' => 0, 'comment' => 'Number of job attempts made'], $unsigned))
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'comment' => 'Record creation timestamp'])
            ->addIndex(['runtemplate_id'])
            ->addIndex(['state'])
            ->addIndex(['window_start', 'window_end'])
            ->create();

        $table
            ->addForeignKey('runtemplate_id', 'runtemplate', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->save();
    }
}
