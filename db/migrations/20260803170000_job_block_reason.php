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

final class JobBlockReason extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('job');

        if ($table->hasColumn('block_reason')) {
            return;
        }

        $table
            ->addColumn('block_reason', 'text', ['null' => true, 'default' => null, 'comment' => 'Why the job could not launch (e.g. credential availability check failure); null when the job ran normally'])
            ->addColumn('blocked_at', 'datetime', ['null' => true, 'default' => null, 'comment' => 'Timestamp of the most recent block, for display alongside block_reason'])
            ->save();
    }
}
