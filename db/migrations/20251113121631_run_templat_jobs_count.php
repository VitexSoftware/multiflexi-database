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

final class RunTemplatJobsCount extends AbstractMigration
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
        $table = $this->table('runtemplate');
        $table
            ->addColumn('DatCreate', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'comment' => 'Timestamp when the Runtemplate record was created'])
            ->addColumn('DatSave', 'datetime', ['null' => true, 'comment' => 'Last modification timestamp'])
            ->addColumn('successfull_jobs_count', 'integer', ['signed' => false, 'null' => false, 'default' => 0, 'comment' => 'Count of successfully completed jobs'])
            ->addColumn('failed_jobs_count', 'integer', ['signed' => false, 'null' => false, 'default' => 0, 'comment' => 'Count of failed jobs']);

        $table->update();
    }
}
