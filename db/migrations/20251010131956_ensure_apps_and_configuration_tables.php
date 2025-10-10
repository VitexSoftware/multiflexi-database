<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EnsureAppsAndConfigurationTables extends AbstractMigration
{
    public function up(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // The configuration table exists but without an id column
        // We need to add one for the foreign key relationship
        if ($this->hasTable('configuration')) {
            $table = $this->table('configuration');
            $columns = $this->getAdapter()->getColumns('configuration');
            $hasIdColumn = false;
            
            foreach ($columns as $column) {
                if ($column->getName() === 'id') {
                    $hasIdColumn = true;
                    break;
                }
            }
            
            if (!$hasIdColumn) {
                // The table exists without id, we need to add it
                // This is complex because the table was created without specifying id: false
                // Phinx by default adds an id column, so this table must have been created differently
                
                // Create a temporary table with the structure we want
                $tempTable = $this->table('configuration_temp');
                $tempTable
                    ->addColumn('app_id', 'integer', array_merge(['null' => true], $unsigned))
                    ->addColumn('company_id', 'integer', array_merge(['null' => true], $unsigned))
                    ->addColumn('runtemplate_id', 'integer', array_merge(['null' => true], $unsigned))
                    ->addColumn('key', 'string', ['limit' => 64, 'null' => true])
                    ->addColumn('name', 'string', ['limit' => 255, 'null' => true])
                    ->addColumn('value', 'text', ['null' => true])
                    ->addColumn('type', 'string', ['limit' => 50, 'null' => true])
                    ->addColumn('description', 'text', ['null' => true])
                    ->addColumn('DatCreate', 'datetime', ['null' => true])
                    ->addColumn('DatUpdate', 'datetime', ['null' => true])
                    ->create();

                // Copy data
                $this->execute('INSERT INTO configuration_temp (app_id, company_id, runtemplate_id, `key`, name, value) ' .
                               'SELECT app_id, company_id, runtemplate_id, `key`, `key` as name, value FROM configuration');

                // Drop old table
                $this->table('configuration')->drop()->save();

                // Rename temp table
                $this->execute('ALTER TABLE configuration_temp RENAME TO configuration');
            }
        }

        // Similarly for conffield table
        if ($this->hasTable('conffield')) {
            $table = $this->table('conffield');
            $columns = $this->getAdapter()->getColumns('conffield');
            $hasIdColumn = false;
            
            foreach ($columns as $column) {
                if ($column->getName() === 'id') {
                    $hasIdColumn = true;
                    break;
                }
            }
            
            if (!$hasIdColumn) {
                // Create temporary table with id
                $tempTable = $this->table('conffield_temp');
                $tempTable
                    ->addColumn('app_id', 'integer', array_merge(['null' => false], $unsigned))
                    ->addColumn('keyname', 'string', ['limit' => 64])
                    ->addColumn('type', 'string', ['limit' => 32])
                    ->addColumn('description', 'text', ['null' => true])
                    ->addColumn('defval', 'string', ['limit' => 255, 'null' => true])
                    ->addColumn('required', 'boolean', ['default' => false])
                    ->addColumn('hint', 'text', ['null' => true])
                    ->create();

                // Copy data
                $this->execute('INSERT INTO conffield_temp (app_id, keyname, type, description) ' .
                               'SELECT app_id, keyname, type, description FROM conffield');

                // Drop old table
                $this->table('conffield')->drop()->save();

                // Rename temp table
                $this->execute('ALTER TABLE conffield_temp RENAME TO conffield');
                
                // Re-add indexes and foreign keys
                $this->table('conffield')
                    ->addIndex(['app_id', 'keyname'], ['unique' => true])
                    ->addForeignKey('app_id', 'apps', 'id', ['delete' => 'CASCADE'])
                    ->save();
            }
        }
    }

    public function down(): void
    {
        // This migration is not easily reversible due to structural changes
        throw new \RuntimeException('This migration cannot be reversed.');
    }
}
