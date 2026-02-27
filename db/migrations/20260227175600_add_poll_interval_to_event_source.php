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

final class AddPollIntervalToEventSource extends AbstractMigration
{
    /**
     * Add poll_interval column to event_source table.
     */
    public function up(): void
    {
        $table = $this->table('event_source');
        
        $table
            ->addColumn('poll_interval', 'integer', [
                'default' => 60,
                'null' => false,
                'comment' => 'Poll interval in seconds',
                'after' => 'last_processed_id',
            ])
            ->update();
    }

    /**
     * Remove poll_interval column from event_source table.
     */
    public function down(): void
    {
        $table = $this->table('event_source');
        $table->removeColumn('poll_interval')->update();
    }
}
