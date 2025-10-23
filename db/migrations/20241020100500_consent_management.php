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

class ConsentManagement extends AbstractMigration
{
    public function change(): void
    {
        // Create the consent table for GDPR compliance
        $table = $this->table('consent');
        $table
            ->addColumn('user_id', 'integer', ['null' => true, 'comment' => 'User ID (null for anonymous users)'])
            ->addColumn('session_id', 'string', ['limit' => 128, 'null' => true, 'comment' => 'Session identifier for anonymous users'])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'comment' => 'IP address of the user'])
            ->addColumn('user_agent', 'text', ['null' => true, 'comment' => 'User agent string'])
            ->addColumn('consent_type', 'string', ['limit' => 64, 'comment' => 'Type of consent (cookies, analytics, marketing, etc.)'])
            ->addColumn('consent_status', 'boolean', ['comment' => 'True for granted, false for denied'])
            ->addColumn('consent_details', 'json', ['null' => true, 'comment' => 'Detailed consent preferences as JSON'])
            ->addColumn('consent_version', 'string', ['limit' => 16, 'default' => '1.0', 'comment' => 'Version of consent policy'])
            ->addColumn('expires_at', 'datetime', ['null' => true, 'comment' => 'When this consent expires'])
            ->addColumn('withdrawn_at', 'datetime', ['null' => true, 'comment' => 'When consent was withdrawn'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'When consent was given'])
            ->addColumn('DatSave', 'datetime', ['null' => true, 'comment' => 'Last modification date'])
            ->addColumn('last_modifier_id', 'integer', ['null' => true, 'comment' => 'ID of last modifier'])
            ->addIndex(['user_id'])
            ->addIndex(['session_id'])
            ->addIndex(['consent_type'])
            ->addIndex(['DatCreate'])
            ->addIndex(['expires_at'])
            ->create();

        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $table
                ->changeColumn('id', 'integer', ['identity' => true])
                ->save();
        }

        // Create consent_log table for audit trail
        $logTable = $this->table('consent_log');
        $logTable
            ->addColumn('consent_id', 'integer', ['comment' => 'Reference to consent record'])
            ->addColumn('user_id', 'integer', ['null' => true, 'comment' => 'User ID'])
            ->addColumn('session_id', 'string', ['limit' => 128, 'null' => true, 'comment' => 'Session identifier'])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'comment' => 'IP address'])
            ->addColumn('action', 'string', ['limit' => 32, 'comment' => 'Action performed (granted, denied, withdrawn, updated)'])
            ->addColumn('consent_type', 'string', ['limit' => 64, 'comment' => 'Type of consent'])
            ->addColumn('old_value', 'json', ['null' => true, 'comment' => 'Previous consent value'])
            ->addColumn('new_value', 'json', ['null' => true, 'comment' => 'New consent value'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'When action was performed'])
            ->addIndex(['consent_id'])
            ->addIndex(['user_id'])
            ->addIndex(['DatCreate'])
            ->addIndex(['action'])
            ->create();

        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $logTable
                ->changeColumn('id', 'integer', ['identity' => true])
                ->save();
        }
    }
}
