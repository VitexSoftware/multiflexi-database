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

class User extends AbstractMigration
{
    public function change(): void
    {
        // Check if the database is MySQL to handle unsigned integers
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // Migration for table users - Core user management table
        $table = $this->table('user', ['comment' => 'Core system users table for authentication and user management']);
        $table
            ->addColumn('enabled', 'boolean', ['default' => false, 'comment' => 'User account status - whether user can log in'])
            ->addColumn('settings', 'text', ['null' => true, 'comment' => 'JSON serialized user preferences and configuration'])
            ->addColumn('email', 'string', ['limit' => 128, 'comment' => 'User email address - used for login and communication'])
            ->addColumn('firstname', 'string', ['null' => true, 'limit' => 32, 'comment' => 'User first name for display purposes'])
            ->addColumn('lastname', 'string', ['null' => true, 'limit' => 32, 'comment' => 'User last name for display purposes'])
            ->addColumn('password', 'string', ['limit' => 40, 'comment' => 'Encrypted password hash (SHA1 - 40 chars)'])
            ->addColumn('login', 'string', ['limit' => 32, 'comment' => 'Unique username for authentication'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'Timestamp when user account was created'])
            ->addColumn('DatSave', 'datetime', ['null' => true, 'comment' => 'Last modification timestamp'])
            ->addColumn('last_modifier_id', 'integer', ['null' => true, 'comment' => 'ID of user who last modified this record'])
            ->addIndex(['login', 'email'], ['unique' => true])                   // Ensure unique login and email combination
            ->create();

        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table
                ->changeColumn('id', 'integer', array_merge(['identity' => true], $unsigned))
                ->save();
        }
    }
}
