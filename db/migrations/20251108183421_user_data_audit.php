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

final class UserDataAudit extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Create user_data_audit table for GDPR Article 16 compliance.
     * Logs all user personal data modifications.
     */
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table('user_data_audit');

        $table->addColumn(
            'user_id',
            'integer',
            array_merge(['null' => false, 'comment' => 'ID of user whose data was changed'], $unsigned),
        )
            ->addColumn('field_name', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Name of the field that was changed',
            ])
            ->addColumn('old_value', 'text', [
                'null' => true,
                'comment' => 'Previous value of the field',
            ])
            ->addColumn('new_value', 'text', [
                'null' => true,
                'comment' => 'New value of the field',
            ])
            ->addColumn('change_type', 'enum', [
                'values' => ['direct', 'pending_approval', 'approved', 'rejected'],
                'default' => 'direct',
                'null' => false,
                'comment' => 'Type of data change operation',
            ])
            ->addColumn(
                'changed_by_user_id',
                'integer',
                array_merge([
                    'null' => true,
                    'comment' => 'ID of user who made the change (null for self-changes)',
                ], $unsigned),
            )
            ->addColumn('ip_address', 'string', [
                'limit' => 45,
                'null' => true,
                'comment' => 'IP address from where change was made',
            ])
            ->addColumn('user_agent', 'text', [
                'null' => true,
                'comment' => 'User agent string of the browser/client',
            ])
            ->addColumn('reason', 'text', [
                'null' => true,
                'comment' => 'Reason for the change (for admin changes)',
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
                'comment' => 'Timestamp when the audit log entry was created',
            ]);

        // Add indexes
        $table->addIndex(['user_id'], ['name' => 'idx_user_id'])
            ->addIndex(['change_type'], ['name' => 'idx_change_type'])
            ->addIndex(['created_at'], ['name' => 'idx_created_at']);

        // Add foreign key constraints
        $table->addForeignKey('user_id', 'user', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ]);

        $table->addForeignKey('changed_by_user_id', 'user', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'NO_ACTION',
        ]);

        $table->create();
    }
}
