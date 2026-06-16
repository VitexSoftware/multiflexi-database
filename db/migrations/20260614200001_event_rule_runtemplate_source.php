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

final class EventRuleRuntemplateSource extends AbstractMigration
{
    /**
     * Add nullable runtemplate_source_id to event_rule so a rule can be
     * triggered by job.completed on a specific RunTemplate instead of (or
     * in addition to) an event_source change event.
     */
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table('event_rule');
        $table
            ->addColumn('runtemplate_source_id', 'integer', array_merge([
                'null' => true,
                'default' => null,
                'after' => 'event_source_id',
                'comment' => 'When set, rule fires on job.completed for this RunTemplate (Phase 3 chaining)',
            ], $unsigned))
            ->addIndex(['runtemplate_source_id', 'enabled'], ['name' => 'idx_event_rule_rt_source_enabled'])
            ->save();
    }
}
