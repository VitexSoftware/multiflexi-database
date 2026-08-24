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
 * Formalizes rbac_permissions / rbac_role_permissions / rbac_role_hierarchy.
 *
 * Same situation as 20260715015632_rbac_roles.php: these tables were only
 * ever created ad hoc, via `CREATE TABLE IF NOT EXISTS` in
 * multiflexi-web5's MultiFlexi\Security\RoleBasedAccessControl constructor,
 * on every request that touched RBAC there — never through a migration, so
 * a freshly provisioned environment without web5 already having run once
 * against it is missing them. This migration formalizes the structure
 * exactly as found in that code, so MultiFlexi\Rbac (multiflexi-core, which
 * web5's RoleBasedAccessControl now delegates the permissions layer to) can
 * rely on them existing without needing to CREATE TABLE itself.
 *
 * rbac_role_hierarchy is formalized for schema completeness (parent/child
 * role inheritance) even though nothing queries it yet.
 */
final class RbacPermissions extends AbstractMigration
{
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        if (!$this->hasTable('rbac_permissions')) {
            $permissions = $this->table('rbac_permissions', ['id' => false, 'primary_key' => ['id'], 'comment' => 'RBAC permission definitions']);
            $permissions->addColumn('id', 'integer', array_merge(['null' => false, 'identity' => true, 'comment' => 'Primary key'], $unsigned))
                ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('resource', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('action', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('is_system', 'boolean', ['default' => false, 'null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['name'], ['unique' => true, 'name' => 'unique_rbac_permissions_name'])
                ->addIndex(['resource', 'action'], ['name' => 'idx_rbac_permissions_resource_action'])
                ->addIndex(['is_system'], ['name' => 'idx_rbac_permissions_system'])
                ->create();
        }

        if (!$this->hasTable('rbac_role_permissions')) {
            $rolePermissions = $this->table('rbac_role_permissions', ['comment' => 'Grants of RBAC permissions to roles']);
            $rolePermissions->addColumn('role_id', 'integer', array_merge(['null' => false], $unsigned))
                ->addColumn('permission_id', 'integer', array_merge(['null' => false], $unsigned))
                ->addColumn('granted_by', 'integer', array_merge(['null' => true], $unsigned))
                ->addColumn('granted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['role_id', 'permission_id'], ['unique' => true, 'name' => 'unique_rbac_role_permissions_role_permission'])
                ->addIndex(['role_id'], ['name' => 'idx_rbac_role_permissions_role_id'])
                ->addIndex(['permission_id'], ['name' => 'idx_rbac_role_permissions_permission_id'])
                ->addIndex(['granted_by'], ['name' => 'idx_rbac_role_permissions_granted_by'])
                ->addForeignKey('role_id', 'rbac_roles', 'id', ['constraint' => 'rbac_role_permissions_ibfk_1', 'delete' => 'CASCADE'])
                ->addForeignKey('permission_id', 'rbac_permissions', 'id', ['constraint' => 'rbac_role_permissions_ibfk_2', 'delete' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('rbac_role_hierarchy')) {
            $roleHierarchy = $this->table('rbac_role_hierarchy', ['comment' => 'Role inheritance (parent grants child roles their permissions)']);
            $roleHierarchy->addColumn('parent_role_id', 'integer', array_merge(['null' => false], $unsigned))
                ->addColumn('child_role_id', 'integer', array_merge(['null' => false], $unsigned))
                ->addIndex(['parent_role_id', 'child_role_id'], ['unique' => true, 'name' => 'unique_rbac_role_hierarchy'])
                ->addIndex(['parent_role_id'], ['name' => 'idx_rbac_role_hierarchy_parent'])
                ->addIndex(['child_role_id'], ['name' => 'idx_rbac_role_hierarchy_child'])
                ->addForeignKey('parent_role_id', 'rbac_roles', 'id', ['constraint' => 'rbac_role_hierarchy_ibfk_1', 'delete' => 'CASCADE'])
                ->addForeignKey('child_role_id', 'rbac_roles', 'id', ['constraint' => 'rbac_role_hierarchy_ibfk_2', 'delete' => 'CASCADE'])
                ->create();
        }
    }
}
