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

/**
 * Tenant scoping of an event source.
 *
 * One acceptor database collects the changes of many AbraFlexi companies
 * (changes_cache.source = changesapi.id, i.e. one row per server/c/company).
 * Without a filter every event source sees every company's changes, so a rule
 * of one tenant would fire on the data of another. server_url limits a source
 * to a single company; NULL keeps the old "everything" behaviour.
 */
final class EventSourceTenantFilter extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('event_source')) {
            return;
        }

        $table = $this->table('event_source');

        if (!$table->hasColumn('server_url')) {
            $table->addColumn('server_url', 'string', [
                'length' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Only changes of this AbraFlexi company (changesapi.serverurl, e.g. https://host:5434/c/firma); null = all companies',
            ])->update();
        }
    }
}
