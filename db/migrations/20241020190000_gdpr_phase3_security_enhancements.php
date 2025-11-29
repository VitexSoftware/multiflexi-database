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
 * GDPR Phase 3: Security Enhancements Migration.
 *
 * This migration implements security features for GDPR compliance:
 * - Two-Factor Authentication
 * - Brute Force Protection
 * - Role-Based Access Control
 * - Enhanced Session Security
 * - Security Audit Logging
 * - Data Encryption Support
 * - API Rate Limiting
 * - IP Whitelisting
 */
class GdprPhase3SecurityEnhancements extends AbstractMigration
{
    public function change(): void
    {
        $this->createTwoFactorAuthTables();
        $this->createBruteForceProtectionTables();
        $this->createRoleBasedAccessControlTables();
        $this->createSecurityAuditTables();
        $this->createSessionSecurityTables();
        $this->createDataEncryptionTables();
        $this->createApiRateLimitingTables();
        $this->createIpWhitelistTables();
        $this->enhanceUserTable();
        $this->enhanceCredentialsTable();
        $this->insertDefaultRoles();
    }

    /**
     * Create Two-Factor Authentication related tables.
     */
    private function createTwoFactorAuthTables(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // Two-Factor Authentication Secrets Table
        $table = $this->table('user_2fa_secrets');
        $table
            ->addColumn('user_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('secret', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('backup_codes', 'json', ['null' => true])
            ->addColumn('enabled', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('last_used', 'timestamp', ['null' => true])
            ->addColumn('recovery_codes_used', 'json', ['null' => true])
            ->addIndex(['user_id'], ['unique' => true]);

        // Add foreign key constraint before create() if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table->addForeignKey('user_id', 'user', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ]);
        }

        $table->create();
    }

    /**
     * Create Brute Force Protection tables.
     */
    private function createBruteForceProtectionTables(): void
    {
        // Login Attempts Table
        $table = $this->table('login_attempts');
        $table
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('username', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('attempt_time', 'datetime', ['null' => false])
            ->addColumn('success', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('failure_reason', 'string', ['limit' => 100, 'null' => true])
            ->addIndex(['ip_address', 'attempt_time'])
            ->addIndex(['username', 'attempt_time'])
            ->addIndex(['success'])
            ->addIndex(['attempt_time'])
            ->create();
    }

    /**
     * Create Role-Based Access Control tables.
     */
    private function createRoleBasedAccessControlTables(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // User Roles Table
        $table = $this->table('user_roles');
        $table
            ->addColumn('name', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('permissions', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // User Role Assignments Table
        $table = $this->table('user_role_assignments');
        $table
            ->addColumn('user_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('role_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('assigned_by', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('assigned_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('expires_at', 'timestamp', ['null' => true])
            ->addIndex(['user_id', 'role_id'], ['unique' => true])
            ->create();

        // Add foreign key constraints if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table->addForeignKey('user_id', 'user', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ]);
            $table->addForeignKey('role_id', 'user_roles', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ]);
            $table->addForeignKey('assigned_by', 'user', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ]);
            $table->save();
        }
    }

    /**
     * Create Security Audit tables.
     */
    private function createSecurityAuditTables(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // Security Audit Log Table
        $table = $this->table('security_audit_log');
        $table
            ->addColumn('user_id', 'integer', array_merge(['null' => true], $unsigned))
            ->addColumn('event_type', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('event_description', 'text', ['null' => false])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('additional_data', 'json', ['null' => true]);

        // Add severity column - use enum for MySQL/PostgreSQL, string for SQLite
        if ($this->adapter->getAdapterType() === 'sqlite') {
            $table->addColumn('severity', 'string', ['limit' => 10, 'default' => 'medium', 'null' => false]);
        } else {
            $table->addColumn('severity', 'enum', ['values' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium']);
        }

        $table->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['event_type'])
            ->addIndex(['severity'])
            ->addIndex(['created_at'])
            ->addIndex(['user_id', 'event_type'])
            ->create();

        // Add foreign key constraint if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table->addForeignKey('user_id', 'user', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])->save();
        }
    }

    /**
     * Create Session Security tables.
     */
    private function createSessionSecurityTables(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // User Sessions Table
        $table = $this->table('user_sessions', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('user_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('last_activity', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('security_data', 'json', ['null' => true])
            ->addIndex(['user_id', 'is_active'])
            ->addIndex(['expires_at'])
            ->addIndex(['last_activity'])
            ->create();

        // Add foreign key constraint if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table->addForeignKey('user_id', 'user', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])->save();
        }
    }

    /**
     * Create Data Encryption tables.
     */
    private function createDataEncryptionTables(): void
    {
        // Encryption Keys Table
        $table = $this->table('encryption_keys');
        $table
            ->addColumn('key_name', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('key_data', 'text', ['null' => false]) // Encrypted key data
            ->addColumn('algorithm', 'string', ['limit' => 50, 'default' => 'AES-256-GCM'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('rotated_at', 'timestamp', ['null' => true])
            ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
            ->addIndex(['key_name'], ['unique' => true])
            ->addIndex(['is_active', 'key_name'])
            ->create();
    }

    /**
     * Create API Rate Limiting tables.
     */
    private function createApiRateLimitingTables(): void
    {
        // API Rate Limits Table
        $table = $this->table('api_rate_limits');
        $table
            ->addColumn('identifier', 'string', ['limit' => 255, 'null' => false]) // IP or API key
            ->addColumn('endpoint', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('requests', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('window_start', 'timestamp', ['null' => false])
            ->addColumn('window_end', 'timestamp', ['null' => false])
            ->addColumn('blocked_until', 'timestamp', ['null' => true])
            ->addIndex(['identifier', 'endpoint'], ['unique' => true])
            ->addIndex(['window_end'])
            ->addIndex(['blocked_until'])
            ->create();
    }

    /**
     * Create IP Whitelisting tables.
     */
    private function createIpWhitelistTables(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // IP Whitelist Table
        $table = $this->table('ip_whitelist');
        $table
            ->addColumn('user_id', 'integer', array_merge(['null' => true], $unsigned))
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('subnet_mask', 'integer', ['default' => 32])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_by', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('last_used', 'timestamp', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addIndex(['ip_address'])
            ->addIndex(['user_id', 'active'])
            ->create();

        // Add foreign key constraints if not SQLite
        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table->addForeignKey('user_id', 'user', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ]);
            $table->addForeignKey('created_by', 'user', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ]);
            $table->save();
        }
    }

    /**
     * Enhance existing User table with security columns.
     */
    private function enhanceUserTable(): void
    {
        $table = $this->table('user');

        // Check if columns already exist before adding them
        if (!$table->hasColumn('password_changed_at')) {
            $table->addColumn('password_changed_at', 'timestamp', ['null' => true, 'after' => 'password']);
        }

        if (!$table->hasColumn('password_expires_at')) {
            $table->addColumn('password_expires_at', 'timestamp', ['null' => true, 'after' => 'password_changed_at']);
        }

        if (!$table->hasColumn('failed_login_attempts')) {
            $table->addColumn('failed_login_attempts', 'integer', ['default' => 0, 'null' => false, 'after' => 'password_expires_at']);
        }

        if (!$table->hasColumn('locked_until')) {
            $table->addColumn('locked_until', 'timestamp', ['null' => true, 'after' => 'failed_login_attempts']);
        }

        if (!$table->hasColumn('two_factor_enabled')) {
            $table->addColumn('two_factor_enabled', 'boolean', ['default' => false, 'null' => false, 'after' => 'locked_until']);
        }

        if (!$table->hasColumn('last_login_ip')) {
            $table->addColumn('last_login_ip', 'string', ['limit' => 45, 'null' => true, 'after' => 'two_factor_enabled']);
        }

        if (!$table->hasColumn('last_login_at')) {
            $table->addColumn('last_login_at', 'timestamp', ['null' => true, 'after' => 'last_login_ip']);
        }

        if (!$table->hasColumn('security_settings')) {
            $table->addColumn('security_settings', 'json', ['null' => true, 'after' => 'last_login_at']);
        }

        // Add indexes for the new security columns
        $table
            ->addIndex(['password_expires_at'])
            ->addIndex(['locked_until'])
            ->addIndex(['two_factor_enabled'])
            ->addIndex(['last_login_at'])
            ->save();
    }

    /**
     * Enhance existing Credential table with encryption support.
     */
    private function enhanceCredentialsTable(): void
    {
        if ($this->hasTable('credential')) {
            // Check if the database is MySQL to handle unsigned integers
            $databaseType = $this->getAdapter()->getOption('adapter');
            $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

            $table = $this->table('credential');

            if (!$table->hasColumn('encrypted_data')) {
                $table->addColumn('encrypted_data', 'text', ['null' => true, 'after' => 'value']);
            }

            if (!$table->hasColumn('encryption_key_id')) {
                $table->addColumn('encryption_key_id', 'integer', array_merge(['null' => true, 'after' => 'encrypted_data'], $unsigned));
            }

            $table->addIndex(['encryption_key_id'])->save();

            // Add foreign key constraint if not SQLite
            if ($this->adapter->getAdapterType() !== 'sqlite') {
                $table->addForeignKey('encryption_key_id', 'encryption_keys', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                ])->save();
            }
        }
    }

    /**
     * Insert default roles.
     */
    private function insertDefaultRoles(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'System Administrator',
                'permissions' => json_encode([
                    'users' => ['create', 'read', 'update', 'delete'],
                    'roles' => ['create', 'read', 'update', 'delete'],
                    'security' => ['read', 'update'],
                    'system' => ['read', 'update', 'backup'],
                    'audit' => ['read'],
                ]),
            ],
            [
                'name' => 'user',
                'description' => 'Standard User',
                'permissions' => json_encode([
                    'profile' => ['read', 'update'],
                    'jobs' => ['create', 'read', 'update'],
                    'companies' => ['create', 'read', 'update'],
                ]),
            ],
            [
                'name' => 'viewer',
                'description' => 'Read-only User',
                'permissions' => json_encode([
                    'profile' => ['read'],
                    'jobs' => ['read'],
                    'companies' => ['read'],
                ]),
            ],
        ];

        $this->table('user_roles')->insert($roles)->saveData();

        // Insert default encryption key placeholder (should be replaced programmatically)
        $this->table('encryption_keys')->insert([
            'key_name' => 'credentials',
            'key_data' => 'PLACEHOLDER_KEY_TO_BE_REPLACED',
            'algorithm' => 'AES-256-GCM',
        ])->saveData();
    }
}
