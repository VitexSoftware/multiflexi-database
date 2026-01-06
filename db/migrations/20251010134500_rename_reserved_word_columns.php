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

final class RenameReservedWordColumns extends AbstractMigration
{
    /**
     * This migration renames columns that use reserved words in various databases
     * to ensure compatibility across MySQL, PostgreSQL, and SQLite.
     */
    public function change(): void
    {
        // Rename 'key' column in configuration table to 'config_key'
        // 'key' is a reserved word in MySQL and PostgreSQL
        if ($this->hasTable('configuration')) {
            $table = $this->table('configuration');

            // Check if the column exists before renaming
            if ($table->hasColumn('key')) {
                $table->renameColumn('key', 'config_key')
                    ->update();
            }
        }

        // Update any other tables that might have reserved word columns
        // Common reserved words across databases: key, type, user, group, order, desc, asc, table, column, index

        // For the 'type' column in configuration table, rename to 'config_type'
        if ($this->hasTable('configuration')) {
            $table = $this->table('configuration');

            if ($table->hasColumn('type')) {
                $table->renameColumn('type', 'config_type')
                    ->update();
            }
        }

        // For any 'user' columns (reserved in PostgreSQL), rename to appropriate names
        // Example: if there's a 'user' column in any table, rename it to 'user_id' or 'username'

        $this->execute(<<<'EOD'

            -- Update any stored procedures, triggers, or views that reference these columns
            -- This is database-specific and might need manual intervention

EOD);
    }
}
