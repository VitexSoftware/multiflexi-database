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

final class Logger extends AbstractMigration
{
    public function change(): void
    {
        // create the log table - System-wide logging for application events and messages
        $table = $this->table('log', ['comment' => 'System-wide logging table for tracking application events and user actions']);
        $table->addColumn('company_id', 'integer', ['null' => true, 'comment' => 'Foreign key to company table - which company this log entry applies to'])
            ->addColumn('apps_id', 'integer', ['null' => true, 'comment' => 'Foreign key to apps table - which application generated this log'])
            ->addColumn('user_id', 'integer', ['null' => true, 'comment' => 'Foreign key to user table - which user was signed in when this happened'])
            ->addColumn('severity', 'string', ['comment' => 'Log level severity (debug, info, warning, error, critical)'])
            ->addColumn('venue', 'string', ['comment' => 'Source component or class that generated this log message'])
            ->addColumn('message', 'text', ['comment' => 'The actual log message content'])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'comment' => 'Timestamp when log entry was created'])
            ->addIndex(['apps_id', 'company_id'], ['unique' => true])
            ->addIndex('user_id')
            ->create();

        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table
                ->changeColumn('id', 'integer', ['identity' => true])
                ->save();
        }
    }
}
