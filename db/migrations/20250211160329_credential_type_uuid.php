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

final class CredentialTypeUuid extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('credential_type');
        
        // Check if uuid column already exists
        if (!$table->hasColumn('uuid')) {
            $adapterType = $this->adapter->getAdapterType();
            
            if ($adapterType === 'mysql') {
                // MySQL 8+ supports uuid() function as default
                $table->addColumn('uuid', 'string', [
                    'length' => 40,
                    'null' => true,
                    'comment' => 'Unique identifier for credential type - auto-generated UUID'
                ]);
                $table->update();
                
                // Set default to auto-generate UUID
                $this->execute('ALTER TABLE `credential_type` CHANGE `uuid` `uuid` VARCHAR(40) NULL DEFAULT (uuid())');
            } elseif ($adapterType === 'pgsql') {
                // PostgreSQL with uuid-ossp extension
                $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
                $table->addColumn('uuid', 'string', [
                    'length' => 40,
                    'null' => true,
                    'default' => 'uuid_generate_v4()',
                    'comment' => 'Unique identifier for credential type - auto-generated UUID'
                ]);
                $table->update();
            } else {
                // SQLite and others - no auto-generation
                $table->addColumn('uuid', 'string', [
                    'length' => 40,
                    'null' => true,
                    'comment' => 'Unique identifier for credential type'
                ]);
                $table->update();
            }
        }
    }
}
