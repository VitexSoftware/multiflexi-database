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

class Customer extends AbstractMigration
{
    public function change(): void
    {
        // Customer table - Similar to users but for external customer accounts
        $table = $this->table('customer', ['comment' => 'External customer accounts - separate from internal system users']);
        $table
            ->addColumn('enabled', 'boolean', ['default' => false, 'comment' => 'Customer account status - whether customer can access services'])
            ->addColumn('settings', 'text', ['null' => true, 'comment' => 'JSON serialized customer preferences and configuration'])
            ->addColumn('email', 'string', ['limit' => 128, 'comment' => 'Customer email address for communication and login'])
            ->addColumn('firstname', 'string', ['null' => true, 'limit' => 32, 'comment' => 'Customer first name for personalization'])
            ->addColumn('lastname', 'string', ['null' => true, 'limit' => 32, 'comment' => 'Customer last name for personalization'])
            ->addColumn('password', 'string', ['limit' => 40, 'comment' => 'Encrypted password hash for customer authentication'])
            ->addColumn('login', 'string', ['limit' => 32, 'comment' => 'Unique username for customer login'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'Timestamp when customer account was created'])
            ->addColumn('DatSave', 'datetime', ['null' => true, 'comment' => 'Last modification timestamp'])
            ->addIndex(['login', 'email'], ['unique' => true])                   // Ensure unique login and email for customers
            ->create();
    }
}
