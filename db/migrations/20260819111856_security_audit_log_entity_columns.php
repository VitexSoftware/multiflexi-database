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
 * Extends security_audit_log with generic entity-CRUD auditing columns so it
 * can record every create/update/delete action performed by any user
 * (web UI, CLI, REST API), not only authentication/security events.
 */
final class SecurityAuditLogEntityColumns extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('security_audit_log');

        if ($table->hasColumn('entity_type')) {
            return;
        }

        $table
            ->addColumn('entity_type', 'string', ['limit' => 100, 'null' => true, 'after' => 'user_id', 'comment' => 'Short class name of the audited entity, e.g. RunTemplate, Company, Credential'])
            ->addColumn('entity_id', 'integer', ['null' => true, 'after' => 'entity_type', 'comment' => 'Primary key of the audited entity row'])
            ->addColumn('action', 'string', ['limit' => 20, 'null' => true, 'after' => 'entity_id', 'comment' => 'create | update | delete'])
            ->addIndex(['entity_type', 'entity_id'])
            ->addIndex(['action'])
            ->save();
    }
}
