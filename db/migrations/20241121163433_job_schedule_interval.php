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

final class JobScheduleInterval extends AbstractMigration
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
        // Set all schedule values to NULL (already present)
        $this->execute('UPDATE job SET schedule=NULL');

        $table = $this->table('job');
        $table
            ->addColumn('schedule_type', 'string', ['comment' => 'Job Schedule type', 'default' => null, 'null' => true])
            ->update();

        // Change column type for PostgreSQL with explicit casting
        if ($this->getAdapter()->getAdapterType() === 'pgsql') {
            $this->execute('ALTER TABLE job ALTER COLUMN schedule TYPE timestamp USING schedule::timestamp');
            $this->execute("COMMENT ON COLUMN job.schedule IS 'Job Schedule time'");
        } else {
            $table->changeColumn('schedule', 'timestamp', ['null' => true, 'comment' => 'Job Schedule time'])->update();
        }
    }
}
