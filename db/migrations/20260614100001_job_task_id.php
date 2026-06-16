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

final class JobTaskId extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('job');

        if ($table->hasColumn('task_id')) {
            return;
        }

        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table
            ->addColumn('task_id', 'integer', array_merge(['null' => true, 'default' => null, 'comment' => 'FK to task; each job belongs to exactly one task (null for manual/legacy jobs)'], $unsigned))
            ->addIndex(['task_id'])
            ->addForeignKey('task_id', 'task', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->save();
    }
}
