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

final class GdprDataExport extends AbstractMigration
{
    public function change(): void
    {
        // Create table for secure download tokens
        $tokenTable = $this->table('data_export_tokens');
        $tokenTable
            ->addColumn('user_id', 'integer', ['comment' => 'User ID who requested the export'])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'comment' => 'SHA256 hash of the token'])
            ->addColumn('format', 'string', ['limit' => 10, 'comment' => 'Export format (json, pdf)'])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'comment' => 'IP address of requester'])
            ->addColumn('user_agent', 'text', ['null' => true, 'comment' => 'User agent string'])
            ->addColumn('expires_at', 'datetime', ['comment' => 'When this token expires'])
            ->addColumn('used_at', 'datetime', ['null' => true, 'comment' => 'When token was used for download'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'When token was created'])
            ->addIndex(['user_id'])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['expires_at'])
            ->addIndex(['used_at'])
            ->create();

        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $tokenTable
                ->changeColumn('id', 'integer', ['identity' => true])
                ->save();
        }

        // Create table for rate limiting data export requests
        $rateLimitTable = $this->table('data_export_rate_limits');
        $rateLimitTable
            ->addColumn('user_id', 'integer', ['comment' => 'User ID'])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'comment' => 'IP address'])
            ->addColumn('request_count', 'integer', ['default' => 1, 'comment' => 'Number of requests in current window'])
            ->addColumn('window_start', 'datetime', ['comment' => 'Start of current rate limit window'])
            ->addColumn('window_end', 'datetime', ['comment' => 'End of current rate limit window'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'First request time'])
            ->addColumn('DatSave', 'datetime', ['null' => true, 'comment' => 'Last request time'])
            ->addIndex(['user_id'])
            ->addIndex(['ip_address'])
            ->addIndex(['window_start'])
            ->addIndex(['window_end'])
            ->addIndex(['user_id', 'ip_address'], ['name' => 'user_ip_idx'])
            ->create();

        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $rateLimitTable
                ->changeColumn('id', 'integer', ['identity' => true])
                ->save();
        }

        // Create table for GDPR audit trail
        $auditTable = $this->table('gdpr_audit_log');
        $auditTable
            ->addColumn('user_id', 'integer', ['null' => true, 'comment' => 'User ID (if applicable)'])
            ->addColumn('action', 'string', ['limit' => 50, 'comment' => 'GDPR action performed'])
            ->addColumn('article', 'string', ['limit' => 20, 'null' => true, 'comment' => 'GDPR Article reference'])
            ->addColumn('data_subject', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Data subject identifier'])
            ->addColumn('legal_basis', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Legal basis for processing'])
            ->addColumn('details', 'json', ['null' => true, 'comment' => 'Additional details as JSON'])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'comment' => 'IP address'])
            ->addColumn('user_agent', 'text', ['null' => true, 'comment' => 'User agent string'])
            ->addColumn('result', 'string', ['limit' => 20, 'comment' => 'Action result (success, failure, partial)'])
            ->addColumn('error_message', 'text', ['null' => true, 'comment' => 'Error message if failed'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'When action was performed'])
            ->addIndex(['user_id'])
            ->addIndex(['action'])
            ->addIndex(['article'])
            ->addIndex(['data_subject'])
            ->addIndex(['DatCreate'])
            ->addIndex(['result'])
            ->create();

        if ($this->adapter->getAdapterType() !== 'sqlite') {
            $auditTable
                ->changeColumn('id', 'integer', ['identity' => true])
                ->save();
        }
    }
}