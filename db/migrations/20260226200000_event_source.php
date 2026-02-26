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

final class EventSource extends AbstractMigration
{
    /**
     * Create the event_source table for storing webhook adapter database connections.
     */
    public function up(): void
    {
        $table = $this->table('event_source');

        $table
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Human-readable name of this event source',
            ])
            ->addColumn('adapter_type', 'string', [
                'limit' => 60,
                'default' => 'abraflexi-webhook',
                'null' => false,
                'comment' => 'Type of webhook adapter (e.g. abraflexi-webhook)',
            ])
            ->addColumn('db_connection', 'string', [
                'limit' => 10,
                'default' => 'mysql',
                'null' => false,
                'comment' => 'Database driver: mysql, pgsql, sqlite',
            ])
            ->addColumn('db_host', 'string', [
                'limit' => 255,
                'default' => 'localhost',
                'null' => false,
                'comment' => 'Database host',
            ])
            ->addColumn('db_port', 'string', [
                'limit' => 10,
                'default' => '3306',
                'null' => false,
                'comment' => 'Database port',
            ])
            ->addColumn('db_database', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Database name or path (for SQLite)',
            ])
            ->addColumn('db_username', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Database username',
            ])
            ->addColumn('db_password', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Database password',
            ])
            ->addColumn('enabled', 'boolean', [
                'default' => true,
                'null' => false,
                'comment' => 'Whether this source is actively polled',
            ])
            ->addColumn('last_processed_id', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => 'Last processed inversion from changes_cache',
            ])
            ->addColumn('created', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addIndex(['name'], ['unique' => true, 'name' => 'idx_event_source_name'])
            ->addIndex(['enabled'], ['name' => 'idx_event_source_enabled'])
            ->create();
    }

    /**
     * Drop the event_source table.
     */
    public function down(): void
    {
        $this->table('event_source')->drop()->save();
    }
}
