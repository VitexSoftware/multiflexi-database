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
 * Formalizes the rbac_roles / rbac_user_roles tables.
 *
 * These tables were created directly against at least one dev database
 * (data present since 2025-10-26) and are already relied upon by
 * `multiflexi-cli user-role:set` and the OpenAPI `getUserRoles`/`setUserRoles`
 * endpoints, but no migration for them was ever committed - so any freshly
 * provisioned environment (including, presumably, production) is missing
 * them entirely. This migration formalizes the structure exactly as found.
 *
 * Distinct from the older, separately migrated `user_roles` /
 * `user_role_assignments` tables (see
 * 20241020190000_gdpr_phase3_security_enhancements.php), which appear
 * unused (0 assignments) - left untouched pending a separate deprecation
 * decision.
 *
 * Index names are prefixed with the table name (idx_rbac_user_roles_user_id,
 * not idx_user_id) even though the original hand-created MySQL table used
 * the short form: SQLite and PostgreSQL both require index names to be
 * unique across the whole database/schema, not just per table, and this
 * database already has an unrelated idx_user_id on user_data_audit -
 * verified against a real SQLite run, not just reasoned about.
 */
final class RbacRoles extends AbstractMigration
{
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        if (!$this->hasTable('rbac_roles')) {
            $roles = $this->table('rbac_roles', ['comment' => 'RBAC role definitions']);
            $roles->addColumn('name', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('display_name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('is_system', 'boolean', ['default' => false, 'null' => false])
                ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['name'], ['unique' => true, 'name' => 'unique_rbac_roles_name'])
                ->addIndex(['is_active'], ['name' => 'idx_rbac_roles_active'])
                ->addIndex(['is_system'], ['name' => 'idx_rbac_roles_system'])
                ->create();
        }

        if (!$this->hasTable('rbac_user_roles')) {
            $userRoles = $this->table('rbac_user_roles', ['comment' => 'Assignment of RBAC roles to users']);
            $userRoles->addColumn('user_id', 'integer', array_merge(['null' => false], $unsigned))
                ->addColumn('role_id', 'integer', array_merge(['null' => false], $unsigned))
                ->addColumn('assigned_by', 'integer', array_merge(['null' => true], $unsigned))
                ->addColumn('assigned_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('expires_at', 'timestamp', ['null' => true, 'default' => null])
                ->addIndex(['user_id', 'role_id'], ['unique' => true, 'name' => 'unique_rbac_user_roles_user_role'])
                ->addIndex(['user_id'], ['name' => 'idx_rbac_user_roles_user_id'])
                ->addIndex(['role_id'], ['name' => 'idx_rbac_user_roles_role_id'])
                ->addIndex(['assigned_by'], ['name' => 'idx_rbac_user_roles_assigned_by'])
                ->addIndex(['expires_at'], ['name' => 'idx_rbac_user_roles_expires_at'])
                ->addForeignKey('role_id', 'rbac_roles', 'id', ['constraint' => 'rbac_user_roles_ibfk_1', 'delete' => 'CASCADE'])
                ->create();
        }

        if ($this->isMigratingUp()) {
            $existing = $this->fetchRow('SELECT id FROM rbac_roles WHERE name = \'super_admin\'');

            if (!$existing) {
                $this->table('rbac_roles')->insert([
                    ['name' => 'super_admin', 'display_name' => 'Super Administrator', 'description' => 'Full system access with all permissions', 'is_system' => 1, 'is_active' => 1],
                    ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Administrative access to most system functions', 'is_system' => 1, 'is_active' => 1],
                    ['name' => 'editor', 'display_name' => 'Editor', 'description' => 'Can create and edit content', 'is_system' => 1, 'is_active' => 1],
                    ['name' => 'user', 'display_name' => 'User', 'description' => 'Basic user access', 'is_system' => 1, 'is_active' => 1],
                    ['name' => 'viewer', 'display_name' => 'Viewer', 'description' => 'Read-only access', 'is_system' => 1, 'is_active' => 1],
                ])->saveData();
            }
        }
    }
}
