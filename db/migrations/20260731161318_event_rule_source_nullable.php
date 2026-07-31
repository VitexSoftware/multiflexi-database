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

final class EventRuleSourceNullable extends AbstractMigration
{
    /**
     * A job-chaining rule (runtemplate_source_id set, added in
     * 20260614200001) has no event_source — only a webhook-triggered rule
     * does. event_source_id was left NOT NULL, which makes chaining rules
     * impossible to save. Relax it; EventruleApi/EventRule enforce that at
     * least one of the two source columns is set.
     */
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $this->table('event_rule')
            ->changeColumn('event_source_id', 'integer', array_merge([
                'null' => true,
                'default' => null,
                'comment' => 'Foreign key to event_source.id (null when runtemplate_source_id is set instead)',
            ], $unsigned))
            ->save();
    }
}
