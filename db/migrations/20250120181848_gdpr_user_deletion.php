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

class GdprUserDeletion extends AbstractMigration
{
    public function change(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // Table for tracking user deletion requests
        $deletionRequests = $this->table('user_deletion_requests');
        $deletionRequests
            ->addColumn('user_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('requested_by_user_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('request_date', 'datetime', ['null' => false])
            ->addColumn('reason', 'text', ['null' => true]);

        // Add enum columns - use enum for MySQL/PostgreSQL, string for SQLite
        if ($this->adapter->getAdapterType() === 'sqlite') {
            $deletionRequests
                ->addColumn('deletion_type', 'string', ['limit' => 20, 'default' => 'soft', 'null' => false])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'null' => false]);
        } else {
            $deletionRequests
                ->addColumn('deletion_type', 'enum', [
                    'values' => ['soft', 'hard', 'anonymize'],
                    'default' => 'soft',
                    'null' => false,
                ])
                ->addColumn('status', 'enum', [
                    'values' => ['pending', 'approved', 'rejected', 'completed'],
                    'default' => 'pending',
                    'null' => false,
                ]);
        }

        $deletionRequests
            ->addColumn('reviewed_by_user_id', 'integer', array_merge(['null' => true], $unsigned))
            ->addColumn('review_date', 'datetime', ['null' => true])
            ->addColumn('review_notes', 'text', ['null' => true])
            ->addColumn('completed_date', 'datetime', ['null' => true])
            ->addColumn('legal_basis', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('retention_period_days', 'integer', ['null' => true])
            ->addColumn('DatCreate', 'datetime', ['null' => false])
            ->addColumn('DatSave', 'datetime', ['null' => true])
            ->addIndex(['user_id'])
            ->addIndex(['status'])
            ->create();

        // Table for deletion audit trail
        $deletionAudit = $this->table('user_deletion_audit');
        $deletionAudit
            ->addColumn('deletion_request_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('table_name', 'string', ['limit' => 128, 'null' => false])
            ->addColumn('record_id', 'integer', ['null' => true]);

        // Add action column - use enum for MySQL/PostgreSQL, string for SQLite
        if ($this->adapter->getAdapterType() === 'sqlite') {
            $deletionAudit->addColumn('action', 'string', ['limit' => 20, 'null' => false]);
        } else {
            $deletionAudit->addColumn('action', 'enum', [
                'values' => ['deleted', 'anonymized', 'retained'],
                'null' => false,
            ]);
        }

        $deletionAudit
            ->addColumn('reason', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('data_before', 'text', ['null' => true])
            ->addColumn('data_after', 'text', ['null' => true])
            ->addColumn('performed_by_user_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('performed_date', 'datetime', ['null' => false])
            ->addIndex(['deletion_request_id'])
            ->addIndex(['table_name'])
            ->create();

        // Add soft delete columns to user table if they don't exist
        $userTable = $this->table('user');

        if (!$userTable->hasColumn('deleted_at')) {
            $userTable->addColumn('deleted_at', 'datetime', ['null' => true]);
        }

        if (!$userTable->hasColumn('deletion_reason')) {
            $userTable->addColumn('deletion_reason', 'string', ['limit' => 255, 'null' => true]);
        }

        if (!$userTable->hasColumn('anonymized_at')) {
            $userTable->addColumn('anonymized_at', 'datetime', ['null' => true]);
        }

        $userTable->save();

        // Foreign key constraints if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $deletionRequests
                ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE'])
                ->addForeignKey('requested_by_user_id', 'user', 'id', ['delete' => 'RESTRICT'])
                ->addForeignKey('reviewed_by_user_id', 'user', 'id', ['delete' => 'SET_NULL'])
                ->save();

            $deletionAudit
                ->addForeignKey('deletion_request_id', 'user_deletion_requests', 'id', ['delete' => 'CASCADE'])
                ->addForeignKey('performed_by_user_id', 'user', 'id', ['delete' => 'RESTRICT'])
                ->save();
        }
    }
}
