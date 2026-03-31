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

final class RuntemplateUnsigned extends AbstractMigration
{
    /**
     * Fix runtemplate.id and all referencing runtemplate_id columns to UNSIGNED on MySQL.
     *
     * runtemplate.id was originally created as signed INT(11) which conflicts with
     * unsigned runtemplate_id FK columns added by later migrations (file_store, event_rule, etc.).
     * This migration converts runtemplate.id to UNSIGNED and fixes all referencing columns
     * to match, then recreates the dropped FK constraints.
     */
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');

        if ($databaseType !== 'mysql') {
            return;
        }

        // Check if runtemplate.id is already unsigned — skip if so
        $rows = $this->fetchAll(<<<'EOD'

            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'runtemplate'
              AND COLUMN_NAME = 'id'

EOD);

        if (!empty($rows) && str_contains(strtolower($rows[0]['COLUMN_TYPE']), 'unsigned')) {
            return;
        }

        // Step 1: Drop FK constraints referencing runtemplate.id (only if they exist)
        $fksToDrop = [
            'actionconfig' => 'runtemplate_must_exist',
            'file_store' => 'file_store_ibfk_2',
            'job' => 'job_runtemplate_fk',
            'runtemplate_topics' => 'r2c_runtemplate_must_exist',
        ];

        foreach ($fksToDrop as $tbl => $constraint) {
            $exists = $this->fetchAll(<<<EOD

                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '{$tbl}'
                  AND CONSTRAINT_NAME = '{$constraint}'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'

EOD);

            if (!empty($exists)) {
                $this->execute("ALTER TABLE `{$tbl}` DROP FOREIGN KEY `{$constraint}`");
            }
        }

        // Step 2: Convert runtemplate.id to UNSIGNED
        $this->execute('ALTER TABLE `runtemplate` CHANGE `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT');

        // Step 3: Convert signed runtemplate_id columns to UNSIGNED (skip if already unsigned or absent)
        $columnsToFix = [
            ['table' => 'actionconfig', 'column' => 'runtemplate_id', 'null' => false],
            ['table' => 'file_store', 'column' => 'runtemplate_id', 'null' => true],
            ['table' => 'job', 'column' => 'runtemplate_id', 'null' => true],
            ['table' => 'runtemplate_topics', 'column' => 'runtemplate_id', 'null' => true],
            ['table' => 'configuration', 'column' => 'runtemplate_id', 'null' => true],
        ];

        foreach ($columnsToFix as $col) {
            $colRows = $this->fetchAll(<<<EOD

                SELECT COLUMN_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '{$col['table']}'
                  AND COLUMN_NAME = '{$col['column']}'

EOD);

            if (empty($colRows) || str_contains(strtolower($colRows[0]['COLUMN_TYPE']), 'unsigned')) {
                continue;
            }

            $nullDef = $col['null'] ? 'NULL DEFAULT NULL' : 'NOT NULL';
            $this->execute("ALTER TABLE `{$col['table']}` CHANGE `{$col['column']}` `{$col['column']}` INT(11) UNSIGNED {$nullDef}");
        }

        // Step 4: Recreate FK constraints matching their original definitions
        $fksToCreate = [
            'actionconfig' => 'ALTER TABLE `actionconfig` ADD CONSTRAINT `runtemplate_must_exist` FOREIGN KEY (`runtemplate_id`) REFERENCES `runtemplate` (`id`)',
            'file_store' => 'ALTER TABLE `file_store` ADD CONSTRAINT `file_store_ibfk_2` FOREIGN KEY (`runtemplate_id`) REFERENCES `runtemplate` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION',
            'job' => 'ALTER TABLE `job` ADD CONSTRAINT `job_runtemplate_fk` FOREIGN KEY (`runtemplate_id`) REFERENCES `runtemplate` (`id`) ON UPDATE CASCADE',
            'runtemplate_topics' => 'ALTER TABLE `runtemplate_topics` ADD CONSTRAINT `r2c_runtemplate_must_exist` FOREIGN KEY (`runtemplate_id`) REFERENCES `runtemplate` (`id`)',
        ];

        foreach ($fksToCreate as $tbl => $sql) {
            if ($this->hasTable($tbl)) {
                $this->execute($sql);
            }
        }
    }
}
