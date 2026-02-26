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

final class EventRule extends AbstractMigration
{
    /**
     * Create the event_rule table for mapping events to RunTemplates.
     */
    public function up(): void
    {
        $table = $this->table('event_rule');

        $table
            ->addColumn('event_source_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to event_source.id',
            ])
            ->addColumn('evidence', 'string', [
                'limit' => 60,
                'null' => true,
                'default' => null,
                'comment' => 'Evidence type filter (null = any evidence)',
            ])
            ->addColumn('operation', 'string', [
                'limit' => 10,
                'default' => 'any',
                'null' => false,
                'comment' => 'Operation filter: create, update, delete, any',
            ])
            ->addColumn('runtemplate_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to runtemplate.id — the job to trigger',
            ])
            ->addColumn('env_mapping', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON object mapping env var names to change record fields',
            ])
            ->addColumn('enabled', 'boolean', [
                'default' => true,
                'null' => false,
                'comment' => 'Whether this rule is active',
            ])
            ->addColumn('priority', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => 'Rule priority (higher = evaluated first)',
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
            ->addForeignKey('event_source_id', 'event_source', ['id'], [
                'constraint' => 'fk_event_rule_source',
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('runtemplate_id', 'runtemplate', ['id'], [
                'constraint' => 'fk_event_rule_runtemplate',
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addIndex(['event_source_id', 'enabled'], ['name' => 'idx_event_rule_source_enabled'])
            ->addIndex(['evidence', 'operation'], ['name' => 'idx_event_rule_evidence_op'])
            ->create();
    }

    /**
     * Drop the event_rule table.
     */
    public function down(): void
    {
        $this->table('event_rule')->drop()->save();
    }
}
