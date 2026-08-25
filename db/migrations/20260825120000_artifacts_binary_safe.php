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
 * Make artifacts.artifact binary-safe.
 *
 * Root cause: the column was created as TEXT/LONGTEXT (utf8mb4 on MySQL).
 * Binary artifacts (e.g. downloaded PDF statements) are not valid UTF-8, so
 * MySQL rejects the INSERT outright:
 *   SQLSTATE[22007]: 1366 Incorrect string value: '\xE2\xE3\xCF\xD3...'
 *   for column `artifacts`.`artifact`
 * Text artifacts (JSON, XML) are valid UTF-8 and insert fine, which is why
 * only binary artifacts silently fail to attach to a job.
 *
 * Column type after this migration:
 *   MySQL      LONGTEXT (utf8mb4) → LONGBLOB (binary, no charset validation)
 *   PostgreSQL TEXT               → BYTEA
 *   SQLite     TEXT               → BLOB (SQLite already stores raw bytes for
 *                                    either type, changed for correctness/clarity)
 */
final class ArtifactsBinarySafe extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('artifacts') || !$this->table('artifacts')->hasColumn('artifact')) {
            return;
        }

        switch ($this->getAdapter()->getAdapterType()) {
            case 'mysql':
                $this->table('artifacts')
                    ->changeColumn('artifact', 'blob', [
                        'limit' => \Phinx\Db\Adapter\MysqlAdapter::BLOB_LONG,
                    ])
                    ->save();

                break;

            case 'pgsql':
                $this->execute('ALTER TABLE artifacts ALTER COLUMN artifact TYPE bytea USING artifact::bytea');

                break;

            case 'sqlsrv':
                $this->execute('ALTER TABLE [artifacts] ALTER COLUMN [artifact] VARBINARY(MAX) NULL');

                break;

            default:
                // SQLite: 'blob' column, no length limit either way
                $this->table('artifacts')
                    ->changeColumn('artifact', 'blob')
                    ->save();

                break;
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('artifacts') || !$this->table('artifacts')->hasColumn('artifact')) {
            return;
        }

        switch ($this->getAdapter()->getAdapterType()) {
            case 'mysql':
                $this->table('artifacts')
                    ->changeColumn('artifact', 'text', [
                        'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG,
                    ])
                    ->save();

                break;

            case 'pgsql':
                $this->execute('ALTER TABLE artifacts ALTER COLUMN artifact TYPE text USING convert_from(artifact, \'UTF8\')');

                break;

            case 'sqlsrv':
                $this->execute('ALTER TABLE [artifacts] ALTER COLUMN [artifact] NTEXT NULL');

                break;

            default:
                $this->table('artifacts')
                    ->changeColumn('artifact', 'text')
                    ->save();

                break;
        }
    }
}
