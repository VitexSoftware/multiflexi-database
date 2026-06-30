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

/**
 * Widen log.message to the maximum text type for each database engine.
 *
 * Root cause: when a job produces output larger than 64 KB (MySQL TEXT limit),
 * the executor subprocess crashes with PDOException SQLSTATE[22001] mid-callback,
 * leaving the job record without end/exitcode.
 *
 * Column capacities after this migration:
 *   MySQL      TEXT (64 KB)  → LONGTEXT  (4 GB)
 *   SQL Server ntext  (~1 GB) → nvarchar(max) (2 GB, replaces deprecated ntext)
 *   PostgreSQL TEXT (unbounded) — no change needed
 *   SQLite     TEXT (unbounded) — no change needed
 */
final class LogMessageLongtext extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('log') || !$this->table('log')->hasColumn('message')) {
            return;
        }

        switch ($this->getAdapter()->getAdapterType()) {
            case 'mysql':
                $this->table('log')
                    ->changeColumn('message', 'text', [
                        'null' => true,
                        'default' => null,
                        'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG,
                        'comment' => 'The actual log message content',
                    ])
                    ->save();
                break;

            case 'sqlsrv':
                // ntext is deprecated since SQL Server 2016; upgrade to nvarchar(max) (2 GB)
                $this->execute('ALTER TABLE [log] ALTER COLUMN [message] NVARCHAR(MAX) NULL');
                break;

            // SQLite and PostgreSQL: TEXT has no size limit — nothing to do
            default:
                break;
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('log') || !$this->table('log')->hasColumn('message')) {
            return;
        }

        switch ($this->getAdapter()->getAdapterType()) {
            case 'mysql':
                $this->table('log')
                    ->changeColumn('message', 'text', [
                        'null' => true,
                        'default' => null,
                        'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR,
                        'comment' => 'The actual log message content',
                    ])
                    ->save();
                break;

            case 'sqlsrv':
                // Revert to ntext — the type Phinx creates for 'text' columns on SQL Server
                $this->execute('ALTER TABLE [log] ALTER COLUMN [message] NTEXT NULL');
                break;

            default:
                break;
        }
    }
}
