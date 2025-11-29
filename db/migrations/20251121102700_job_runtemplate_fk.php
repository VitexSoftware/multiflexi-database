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

final class JobRuntemplateFK extends AbstractMigration
{
    /**
     * Add foreign key constraint on job.runtemplate_id.
     *
     * This prevents deletion of runtemplates that have associated jobs,
     * ensuring referential integrity and preventing orphaned jobs.
     */
    public function change(): void
    {
        $table = $this->table('job');

        // Check if FK already exists
        if (!$this->hasFK()) {
            $table->addForeignKey(
                'runtemplate_id',
                'runtemplate',
                ['id'],
                [
                    'constraint' => 'job_runtemplate_fk',
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ],
            );

            $table->update();
        }
    }

    /**
     * Check if foreign key already exists.
     */
    private function hasFK(): bool
    {
        $databaseType = $this->getAdapter()->getOption('adapter');

        if ($databaseType === 'mysql') {
            $rows = $this->fetchAll(<<<'EOD'

                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'job'
                AND COLUMN_NAME = 'runtemplate_id'
                AND REFERENCED_TABLE_NAME = 'runtemplate'

EOD);

            return !empty($rows);
        }

        // For other databases, assume FK doesn't exist and let Phinx handle it
        return false;
    }
}
