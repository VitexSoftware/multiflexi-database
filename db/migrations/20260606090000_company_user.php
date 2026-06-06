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

final class CompanyUser extends AbstractMigration
{
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // A previous failed run can leave a partially created table behind.
        if ($this->hasTable('company_user')) {
            $this->execute('DROP TABLE IF EXISTS company_user');
        }

        $table = $this->table('company_user', ['comment' => 'Many-to-many relationship between companies and users with access role']);
        $table->addColumn('company_id', 'integer', ['null' => false])
            ->addColumn('user_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('role', 'string', ['null' => false, 'default' => 'viewer'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['company_id', 'user_id'], ['unique' => true, 'name' => 'company_user_company_user_unique'])
            ->addForeignKey('company_id', 'company', 'id', ['constraint' => 'company_user_company_must_exist', 'delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'user', 'id', ['constraint' => 'company_user_user_must_exist', 'delete' => 'CASCADE']);

        $table->create();
    }
}
