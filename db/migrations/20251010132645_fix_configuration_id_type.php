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

final class FixConfigurationIdType extends AbstractMigration
{
    /**
     * This migration detects the actual column types and prepares for translations.
     */
    public function up(): void
    {
        // Check if we're on MySQL/MariaDB
        $databaseType = $this->getAdapter()->getOption('adapter');

        if ($databaseType === 'mysql') {
            // Get the actual column info for configuration.id
            $sql = 'SELECT COLUMN_TYPE, IS_NULLABLE, EXTRA FROM INFORMATION_SCHEMA.COLUMNS '.
                   "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuration' AND COLUMN_NAME = 'id'";
            $configIdInfo = $this->fetchRow($sql);

            // Get the actual column info for apps.id
            $sql = 'SELECT COLUMN_TYPE, IS_NULLABLE, EXTRA FROM INFORMATION_SCHEMA.COLUMNS '.
                   "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'apps' AND COLUMN_NAME = 'id'";
            $appsIdInfo = $this->fetchRow($sql);

            // Store the column types in a temporary table for the next migration
            $this->execute('CREATE TEMPORARY TABLE IF NOT EXISTS _migration_column_types ('.
                          'table_name VARCHAR(64), '.
                          'column_name VARCHAR(64), '.
                          'column_type VARCHAR(64), '.
                          'is_unsigned BOOLEAN'.
                          ')');

            // Check if configuration.id is unsigned
            $configIsUnsigned = str_contains($configIdInfo['COLUMN_TYPE'], 'unsigned');
            $appsIsUnsigned = str_contains($appsIdInfo['COLUMN_TYPE'], 'unsigned');

            echo 'Configuration ID type: '.$configIdInfo['COLUMN_TYPE'].' (unsigned: '.($configIsUnsigned ? 'yes' : 'no').")\n";
            echo 'Apps ID type: '.$appsIdInfo['COLUMN_TYPE'].' (unsigned: '.($appsIsUnsigned ? 'yes' : 'no').")\n";

            // Fix the AddTranslations migration to use correct types
            // We'll update the migration file directly
            $migrationFile = __DIR__.'/20251008184819_add_translations.php';

            if (file_exists($migrationFile)) {
                $content = file_get_contents($migrationFile);

                // If configuration.id is signed, we need to use signed integers for the foreign key
                if (!$configIsUnsigned) {
                    $newContent = str_replace(
                        "->addColumn('configuration_id', 'integer', array_merge(['null' => false], \$unsigned))",
                        "->addColumn('configuration_id', 'integer', ['null' => false, 'signed' => true])",
                        $content,
                    );

                    if ($newContent !== $content) {
                        file_put_contents($migrationFile, $newContent);
                        echo "Updated AddTranslations migration to use signed integer for configuration_id\n";
                    }
                }

                // If apps.id is signed, we need to use signed integers for the foreign key
                if (!$appsIsUnsigned) {
                    $content = file_get_contents($migrationFile);
                    $newContent = str_replace(
                        "->addColumn('app_id', 'integer', array_merge(['null' => false], \$unsigned))",
                        "->addColumn('app_id', 'integer', ['null' => false, 'signed' => true])",
                        $content,
                    );

                    if ($newContent !== $content) {
                        file_put_contents($migrationFile, $newContent);
                        echo "Updated AddTranslations migration to use signed integer for app_id\n";
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Not reversible
    }
}
