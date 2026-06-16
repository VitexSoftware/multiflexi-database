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

final class RuntemplateRetryConfig extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('runtemplate');

        if (!$table->hasColumn('deadline_offset')) {
            $table->addColumn('deadline_offset', 'string', [
                'length' => 32,
                'null' => true,
                'default' => null,
                'comment' => 'Deadline offset from window start: "+3h" or absolute time "08:00". NULL = window_end.',
            ]);
        }

        if (!$table->hasColumn('max_attempts')) {
            $table->addColumn('max_attempts', 'integer', [
                'null' => false,
                'default' => 1,
                'comment' => 'Maximum number of job attempts per task window.',
                'signed' => false,
            ]);
        }

        if (!$table->hasColumn('retry_backoff')) {
            $table->addColumn('retry_backoff', 'string', [
                'length' => 16,
                'null' => false,
                'default' => 'none',
                'comment' => 'Retry backoff strategy: none | fixed | linear | exponential.',
            ]);
        }

        if (!$table->hasColumn('retry_min_gap')) {
            $table->addColumn('retry_min_gap', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Minimum seconds between retry attempts.',
                'signed' => false,
            ]);
        }

        if (!$table->hasColumn('allow_late')) {
            $table->addColumn('allow_late', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Whether a post-deadline success counts as fulfilled_late.',
            ]);
        }

        $table->save();
    }
}
