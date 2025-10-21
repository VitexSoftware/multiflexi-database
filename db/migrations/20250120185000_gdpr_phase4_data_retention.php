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
 * GDPR Phase 4: Data Retention and Deletion Policies Migration
 * 
 * This migration implements data retention and deletion features for GDPR compliance:
 * - Data retention policies configuration
 * - Automated cleanup jobs tracking
 * - Data archival before deletion
 * - Grace periods for deletion requests
 * - Retention reporting and confirmations
 */
class GdprPhase4DataRetention extends AbstractMigration
{
    public function change(): void
    {
        $this->createDataRetentionPoliciesTable();
        $this->createRetentionJobsTable();
        $this->createDataArchiveTable();
        $this->createRetentionReportsTable();
        $this->enhanceExistingTablesWithRetentionFields();
        $this->insertDefaultRetentionPolicies();
    }

    /**
     * Create data retention policies configuration table
     */
    private function createDataRetentionPoliciesTable(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table('data_retention_policies');
        $table
            ->addColumn('policy_name', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Unique policy identifier'])
            ->addColumn('data_type', 'string', ['limit' => 50, 'null' => false, 'comment' => 'Type of data this policy applies to'])
            ->addColumn('table_name', 'string', ['limit' => 64, 'null' => false, 'comment' => 'Database table name'])
            ->addColumn('retention_period_days', 'integer', ['null' => false, 'comment' => 'Retention period in days'])
            ->addColumn('grace_period_days', 'integer', ['default' => 30, 'null' => false, 'comment' => 'Grace period before actual deletion']);

        // Add deletion_action column - use enum for MySQL/PostgreSQL, string for SQLite
        if ($this->adapter->getAdapterType() === 'sqlite') {
            $table->addColumn('deletion_action', 'string', ['limit' => 20, 'default' => 'soft_delete', 'null' => false, 'comment' => 'Action to take when retention period expires']);
        } else {
            $table->addColumn('deletion_action', 'enum', [
                'values' => ['hard_delete', 'soft_delete', 'anonymize', 'archive'],
                'default' => 'soft_delete',
                'null' => false,
                'comment' => 'Action to take when retention period expires'
            ]);
        }

        $table->addColumn('legal_basis', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Legal basis for retention period'])
            ->addColumn('description', 'text', ['null' => true, 'comment' => 'Human-readable description of the policy'])
            ->addColumn('enabled', 'boolean', ['default' => true, 'null' => false, 'comment' => 'Whether this policy is active'])
            ->addColumn('created_by', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['policy_name'], ['unique' => true])
            ->addIndex(['data_type'])
            ->addIndex(['table_name'])
            ->addIndex(['enabled'])
            ->create();

        // Add foreign key constraint if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table->addForeignKey('created_by', 'user', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION'
            ])->save();
        }
    }

    /**
     * Create retention cleanup jobs tracking table
     */
    private function createRetentionJobsTable(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table('retention_cleanup_jobs');
        $table
            ->addColumn('policy_id', 'integer', array_merge(['null' => false], $unsigned));

        // Add enum columns - use enum for MySQL/PostgreSQL, string for SQLite
        if ($this->adapter->getAdapterType() === 'sqlite') {
            $table
                ->addColumn('job_type', 'string', ['limit' => 30, 'null' => false, 'comment' => 'Type of cleanup job'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'null' => false]);
        } else {
            $table
                ->addColumn('job_type', 'enum', [
                    'values' => ['scheduled_cleanup', 'manual_cleanup', 'grace_period_cleanup'],
                    'null' => false,
                    'comment' => 'Type of cleanup job'
                ])
                ->addColumn('status', 'enum', [
                    'values' => ['pending', 'running', 'completed', 'failed', 'cancelled'],
                    'default' => 'pending',
                    'null' => false
                ]);
        }

        $table->addColumn('started_by', 'integer', array_merge(['null' => true], $unsigned))
            ->addColumn('started_at', 'timestamp', ['null' => true])
            ->addColumn('completed_at', 'timestamp', ['null' => true])
            ->addColumn('records_processed', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('records_deleted', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('records_anonymized', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('records_archived', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('errors', 'json', ['null' => true, 'comment' => 'Any errors encountered during cleanup'])
            ->addColumn('summary', 'text', ['null' => true, 'comment' => 'Human-readable job summary'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['policy_id'])
            ->addIndex(['status'])
            ->addIndex(['job_type'])
            ->addIndex(['started_at'])
            ->create();

        // Add foreign key constraints if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table
                ->addForeignKey('policy_id', 'data_retention_policies', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION'
                ])
                ->addForeignKey('started_by', 'user', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION'
                ])
                ->save();
        }
    }

    /**
     * Create data archive table for storing deleted data
     */
    private function createDataArchiveTable(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table('data_archive');
        $table;

        // Add archive_type column - use enum for MySQL/PostgreSQL, string for SQLite
        if ($this->adapter->getAdapterType() === 'sqlite') {
            $table->addColumn('archive_type', 'string', ['limit' => 30, 'null' => false, 'comment' => 'Type of archive']);
        } else {
            $table->addColumn('archive_type', 'enum', [
                'values' => ['pre_deletion', 'anonymization_backup', 'legal_hold'],
                'null' => false,
                'comment' => 'Type of archive'
            ]);
        }

        $table->addColumn('source_table', 'string', ['limit' => 64, 'null' => false, 'comment' => 'Original table name'])
            ->addColumn('source_record_id', 'integer', ['null' => false, 'comment' => 'Original record ID'])
            ->addColumn('archived_data', 'text', ['null' => false, 'comment' => 'JSON-encoded original data'])
            ->addColumn('retention_job_id', 'integer', array_merge(['null' => true], $unsigned))
            ->addColumn('archived_reason', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Reason for archiving'])
            ->addColumn('legal_hold_until', 'datetime', ['null' => true, 'comment' => 'Legal hold expiration date'])
            ->addColumn('archived_by', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('archived_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['source_table', 'source_record_id'])
            ->addIndex(['archive_type'])
            ->addIndex(['archived_at'])
            ->addIndex(['legal_hold_until'])
            ->create();

        // Add foreign key constraints if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table
                ->addForeignKey('retention_job_id', 'retention_cleanup_jobs', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION'
                ])
                ->addForeignKey('archived_by', 'user', 'id', [
                    'delete' => 'RESTRICT',
                    'update' => 'NO_ACTION'
                ])
                ->save();
        }
    }

    /**
     * Create retention reports table
     */
    private function createRetentionReportsTable(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table('retention_reports');
        $table;

        // Add report_type column - use enum for MySQL/PostgreSQL, string for SQLite
        if ($this->adapter->getAdapterType() === 'sqlite') {
            $table->addColumn('report_type', 'string', ['limit' => 30, 'null' => false]);
        } else {
            $table->addColumn('report_type', 'enum', [
                'values' => ['daily_summary', 'weekly_summary', 'monthly_summary', 'policy_audit', 'compliance_report'],
                'null' => false
            ]);
        }

        $table->addColumn('report_period_start', 'datetime', ['null' => false])
            ->addColumn('report_period_end', 'datetime', ['null' => false])
            ->addColumn('generated_by', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('report_data', 'json', ['null' => false, 'comment' => 'Structured report data'])
            ->addColumn('summary', 'text', ['null' => true, 'comment' => 'Human-readable report summary'])
            ->addColumn('file_path', 'string', ['limit' => 500, 'null' => true, 'comment' => 'Path to exported report file'])
            ->addColumn('generated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['report_type'])
            ->addIndex(['report_period_start', 'report_period_end'])
            ->addIndex(['generated_at'])
            ->create();

        // Add foreign key constraint if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table->addForeignKey('generated_by', 'user', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION'
            ])->save();
        }
    }

    /**
     * Enhance existing tables with retention-related fields
     */
    private function enhanceExistingTablesWithRetentionFields(): void
    {
        // Add retention fields to log table
        if ($this->hasTable('log')) {
            $logTable = $this->table('log');
            if (!$logTable->hasColumn('retention_until')) {
                $logTable->addColumn('retention_until', 'datetime', ['null' => true, 'comment' => 'Calculated retention expiration date']);
            }
            if (!$logTable->hasColumn('marked_for_deletion')) {
                $logTable->addColumn('marked_for_deletion', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Whether record is scheduled for deletion']);
            }
            $logTable->addIndex(['retention_until']);
            $logTable->addIndex(['marked_for_deletion']);
            $logTable->save();
        }

        // Add retention fields to job table
        if ($this->hasTable('job')) {
            $jobTable = $this->table('job');
            if (!$jobTable->hasColumn('retention_until')) {
                $jobTable->addColumn('retention_until', 'datetime', ['null' => true, 'comment' => 'Calculated retention expiration date']);
            }
            if (!$jobTable->hasColumn('marked_for_deletion')) {
                $jobTable->addColumn('marked_for_deletion', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Whether record is scheduled for deletion']);
            }
            $jobTable->addIndex(['retention_until']);
            $jobTable->addIndex(['marked_for_deletion']);
            $jobTable->save();
        }

        // Add retention fields to security_audit_log table
        if ($this->hasTable('security_audit_log')) {
            $auditTable = $this->table('security_audit_log');
            if (!$auditTable->hasColumn('retention_until')) {
                $auditTable->addColumn('retention_until', 'datetime', ['null' => true, 'comment' => 'Calculated retention expiration date']);
            }
            if (!$auditTable->hasColumn('marked_for_deletion')) {
                $auditTable->addColumn('marked_for_deletion', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Whether record is scheduled for deletion']);
            }
            $auditTable->addIndex(['retention_until']);
            $auditTable->addIndex(['marked_for_deletion']);
            $auditTable->save();
        }

        // Add retention fields to user_sessions table  
        if ($this->hasTable('user_sessions')) {
            $sessionTable = $this->table('user_sessions');
            if (!$sessionTable->hasColumn('retention_until')) {
                $sessionTable->addColumn('retention_until', 'datetime', ['null' => true, 'comment' => 'Calculated retention expiration date']);
            }
            if (!$sessionTable->hasColumn('marked_for_deletion')) {
                $sessionTable->addColumn('marked_for_deletion', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Whether record is scheduled for deletion']);
            }
            $sessionTable->addIndex(['retention_until']);
            $sessionTable->addIndex(['marked_for_deletion']);
            $sessionTable->save();
        }

        // Add retention fields to company table
        if ($this->hasTable('company')) {
            $companyTable = $this->table('company');
            if (!$companyTable->hasColumn('retention_until')) {
                $companyTable->addColumn('retention_until', 'datetime', ['null' => true, 'comment' => 'Calculated retention expiration date']);
            }
            if (!$companyTable->hasColumn('marked_for_deletion')) {
                $companyTable->addColumn('marked_for_deletion', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Whether record is scheduled for deletion']);
            }
            $companyTable->addIndex(['retention_until']);
            $companyTable->addIndex(['marked_for_deletion']);
            $companyTable->save();
        }

        // Add retention fields to user table
        if ($this->hasTable('user')) {
            $userTable = $this->table('user');
            if (!$userTable->hasColumn('last_activity_at')) {
                $userTable->addColumn('last_activity_at', 'timestamp', ['null' => true, 'comment' => 'Last user activity timestamp']);
            }
            if (!$userTable->hasColumn('inactive_since')) {
                $userTable->addColumn('inactive_since', 'datetime', ['null' => true, 'comment' => 'Date when user became inactive']);
            }
            if (!$userTable->hasColumn('retention_until')) {
                $userTable->addColumn('retention_until', 'datetime', ['null' => true, 'comment' => 'Calculated retention expiration date']);
            }
            $userTable->addIndex(['last_activity_at']);
            $userTable->addIndex(['inactive_since']);
            $userTable->addIndex(['retention_until']);
            $userTable->save();
        }
    }

    /**
     * Insert default retention policies according to GDPR requirements
     */
    private function insertDefaultRetentionPolicies(): void
    {
        // Get first admin user ID for created_by field
        $adminUser = $this->fetchRow('SELECT id FROM user WHERE enabled = 1 ORDER BY id LIMIT 1');
        $adminUserId = $adminUser['id'] ?? 1;

        $policies = [
            [
                'policy_name' => 'user_accounts_inactive',
                'data_type' => 'user_personal_data',
                'table_name' => 'user',
                'retention_period_days' => 1095, // 3 years
                'grace_period_days' => 30,
                'deletion_action' => 'anonymize',
                'legal_basis' => 'GDPR Art. 5(1)(e) - Data minimization principle',
                'description' => 'Inactive user accounts are anonymized after 3 years of inactivity',
                'created_by' => $adminUserId
            ],
            [
                'policy_name' => 'session_data',
                'data_type' => 'session_data',
                'table_name' => 'user_sessions',
                'retention_period_days' => 30,
                'grace_period_days' => 7,
                'deletion_action' => 'hard_delete',
                'legal_basis' => 'GDPR Art. 5(1)(e) - Data minimization principle',
                'description' => 'Session data is deleted after 30 days',
                'created_by' => $adminUserId
            ],
            [
                'policy_name' => 'audit_logs',
                'data_type' => 'audit_data',
                'table_name' => 'security_audit_log',
                'retention_period_days' => 2555, // 7 years
                'grace_period_days' => 90,
                'deletion_action' => 'archive',
                'legal_basis' => 'Legal and regulatory requirements for audit trail retention',
                'description' => 'Security audit logs are archived after 7 years',
                'created_by' => $adminUserId
            ],
            [
                'policy_name' => 'job_execution_logs',
                'data_type' => 'job_execution_data',
                'table_name' => 'job',
                'retention_period_days' => 365, // 1 year
                'grace_period_days' => 30,
                'deletion_action' => 'soft_delete',
                'legal_basis' => 'Business operations and troubleshooting requirements',
                'description' => 'Job execution logs are soft deleted after 1 year',
                'created_by' => $adminUserId
            ],
            [
                'policy_name' => 'application_logs',
                'data_type' => 'application_logs',
                'table_name' => 'log',
                'retention_period_days' => 365, // 1 year
                'grace_period_days' => 30,
                'deletion_action' => 'hard_delete',
                'legal_basis' => 'Business operations and troubleshooting requirements',
                'description' => 'Application logs are deleted after 1 year',
                'created_by' => $adminUserId
            ],
            [
                'policy_name' => 'company_data_business_relationship',
                'data_type' => 'company_business_data',
                'table_name' => 'company',
                'retention_period_days' => 1825, // 5 years (based on business relationship)
                'grace_period_days' => 90,
                'deletion_action' => 'anonymize',
                'legal_basis' => 'Legitimate business interests and contract obligations',
                'description' => 'Company data retained based on active business relationship, anonymized after 5 years of inactivity',
                'created_by' => $adminUserId
            ],
            [
                'policy_name' => 'login_attempts',
                'data_type' => 'security_data',
                'table_name' => 'login_attempts',
                'retention_period_days' => 90, // 3 months
                'grace_period_days' => 7,
                'deletion_action' => 'hard_delete',
                'legal_basis' => 'Security monitoring and fraud prevention',
                'description' => 'Login attempt logs are deleted after 3 months',
                'created_by' => $adminUserId
            ]
        ];

        $this->table('data_retention_policies')->insert($policies)->saveData();
    }

    /**
     * Rollback migration
     */
    public function down(): void
    {
        // Remove added retention columns from existing tables
        $tables = ['log', 'job', 'security_audit_log', 'user_sessions', 'company'];
        foreach ($tables as $tableName) {
            if ($this->hasTable($tableName)) {
                $table = $this->table($tableName);
                if ($table->hasColumn('marked_for_deletion')) {
                    $table->removeColumn('marked_for_deletion');
                }
                if ($table->hasColumn('retention_until')) {
                    $table->removeColumn('retention_until');
                }
                $table->save();
            }
        }

        // Remove user activity columns
        if ($this->hasTable('user')) {
            $userTable = $this->table('user');
            if ($userTable->hasColumn('retention_until')) {
                $userTable->removeColumn('retention_until');
            }
            if ($userTable->hasColumn('inactive_since')) {
                $userTable->removeColumn('inactive_since');
            }
            if ($userTable->hasColumn('last_activity_at')) {
                $userTable->removeColumn('last_activity_at');
            }
            $userTable->save();
        }

        // Drop retention tables
        $this->table('retention_reports')->drop()->save();
        $this->table('data_archive')->drop()->save();
        $this->table('retention_cleanup_jobs')->drop()->save();
        $this->table('data_retention_policies')->drop()->save();
    }
}