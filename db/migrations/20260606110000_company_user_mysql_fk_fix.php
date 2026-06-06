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

final class CompanyUserMysqlFkFix extends AbstractMigration
{
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');

        if ($databaseType !== 'mysql' || !$this->hasTable('company_user')) {
            return;
        }

        $columns = $this->fetchAll(<<<'EOD'

            SELECT COLUMN_NAME, COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'company_user'
              AND COLUMN_NAME IN ('company_id', 'user_id')

EOD);

        foreach ($columns as $column) {
            if (!str_contains(strtolower((string) $column['COLUMN_TYPE']), 'unsigned')) {
                $name = (string) $column['COLUMN_NAME'];
                $this->execute("ALTER TABLE `company_user` CHANGE `{$name}` `{$name}` INT(11) UNSIGNED NOT NULL");
            }
        }

        $constraints = $this->fetchAll(<<<'EOD'

            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'company_user'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'

EOD);

        $constraintNames = array_column($constraints, 'CONSTRAINT_NAME');

        if (!in_array('company_user_company_must_exist', $constraintNames, true)) {
            $this->execute('ALTER TABLE `company_user` ADD CONSTRAINT `company_user_company_must_exist` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE');
        }

        if (!in_array('company_user_user_must_exist', $constraintNames, true)) {
            $this->execute('ALTER TABLE `company_user` ADD CONSTRAINT `company_user_user_must_exist` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE');
        }
    }
}
