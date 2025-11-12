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

final class UserDataCorrectionRequests extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Create user_data_correction_requests table for GDPR Article 16 compliance.
     * Manages user requests to correct their personal data.
     */
    public function change(): void
    {

        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];


        $table = $this->table('user_data_correction_requests');

        $table->addColumn('user_id', 'integer',
        array_merge(
        [
            'null' => false,
            'comment' => 'ID of user requesting data correction',
        ], $unsigned))
            ->addColumn('field_name', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Name of the field to be corrected',
            ])
            ->addColumn('current_value', 'text', [
                'null' => true,
                'comment' => 'Current value of the field',
            ])
            ->addColumn('requested_value', 'text', [
                'null' => true,
                'comment' => 'Requested new value for the field',
            ])
            ->addColumn('justification', 'text', [
                'null' => true,
                'comment' => 'User justification for the correction request',
            ])
            ->addColumn('status', 'enum', [
                'values' => ['pending', 'approved', 'rejected', 'cancelled'],
                'default' => 'pending',
                'null' => false,
                'comment' => 'Status of the correction request',
            ])
            ->addColumn('requested_by_ip', 'string', [
                'limit' => 45,
                'null' => true,
                'comment' => 'IP address from where the request was made',
            ])
            ->addColumn('requested_by_user_agent', 'text',
                array_merge(
                    [
                        'null' => true,
                        'comment' => 'User agent string of the browser/client',
                    ], $unsigned
                )
            )
            ->addColumn('reviewed_by_user_id', 'integer',
                array_merge(
                    [
                        'null' => true,
                        'comment' => 'ID of admin user who reviewed the request',
                    ], $unsigned
                )
            )
            ->addColumn('reviewed_at', 'timestamp', [
                'null' => true,
                'comment' => 'Timestamp when the request was reviewed',
            ])
            ->addColumn('reviewer_notes', 'text', [
                'null' => true,
                'comment' => 'Notes added by the reviewer',
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
                'comment' => 'Timestamp when the request was created',
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false,
                'comment' => 'Timestamp when the request was last updated',
            ]);

        // Add indexes
        $table->addIndex(['user_id'], ['name' => 'idx_user_id'])
            ->addIndex(['status'], ['name' => 'idx_status'])
            ->addIndex(['created_at'], ['name' => 'idx_created_at']);

        // Add foreign key constraints
        $table->addForeignKey('user_id', 'user', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ]);

        $table->addForeignKey('reviewed_by_user_id', 'user', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'NO_ACTION',
        ]);

        $table->create();
    }
}
