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

final class JobOutputLines extends AbstractMigration
{
    public function up(): void
    {
        $adapter = $this->getAdapter()->getAdapterType();
        $unsigned = ($adapter === 'mysql') ? ['signed' => false] : [];

        // 1. Create job_output_lines table
        $table = $this->table('job_output_lines', ['id' => true, 'primary_key' => ['id']]);

        $createdAtOptions = ($adapter === 'mysql')
            ? ['null' => false, 'default' => 'CURRENT_TIMESTAMP(6)', 'precision' => 6, 'comment' => 'Microsecond-precision timestamp']
            : ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'comment' => 'Timestamp'];

        $table
            ->addColumn('job_id', 'integer', array_merge(['null' => false, 'comment' => 'FK to job'], $unsigned))
            ->addColumn('seq', 'integer', array_merge(['null' => false, 'default' => 0, 'comment' => 'Line sequence within job'], $unsigned))
            ->addColumn('type', 'string', ['limit' => 16, 'null' => false, 'default' => 'stdout', 'comment' => 'Line type: stdout | stderr | info | warning | error | debug | …'])
            ->addColumn('line', 'text', ['null' => false, 'default' => ''])
            ->addColumn('created_at', 'datetime', $createdAtOptions)
            ->addIndex(['job_id', 'seq'])
            ->addIndex(['job_id', 'id'])
            ->create();

        $table->addForeignKey('job_id', 'job', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])->save();

        // 2. Migrate existing stdout / stderr from job table in batches
        if ($this->hasTable('job')) {
            $jobTable = $this->table('job');

            if ($jobTable->hasColumn('stdout') || $jobTable->hasColumn('stderr')) {
                $offset = 0;
                $batchSize = 500;

                do {
                    $rows = $this->fetchAll(
                        "SELECT id, stdout, stderr FROM job WHERE (stdout IS NOT NULL AND stdout != '') OR (stderr IS NOT NULL AND stderr != '') LIMIT {$batchSize} OFFSET {$offset}",
                    );

                    foreach ($rows as $row) {
                        $inserts = [];
                        $seq = 0;

                        if (!empty($row['stdout'])) {
                            foreach (explode("\n", stripslashes((string) $row['stdout'])) as $line) {
                                $safe = str_replace("'", "''", $line);
                                $inserts[] = "({$row['id']}, {$seq}, 'stdout', '{$safe}')";
                                ++$seq;
                            }
                        }

                        if (!empty($row['stderr'])) {
                            foreach (explode("\n", stripslashes((string) $row['stderr'])) as $line) {
                                $safe = str_replace("'", "''", $line);
                                $inserts[] = "({$row['id']}, {$seq}, 'stderr', '{$safe}')";
                                ++$seq;
                            }
                        }

                        if ($inserts) {
                            $this->execute(
                                'INSERT INTO job_output_lines (job_id, seq, type, line) VALUES '.implode(',', $inserts),
                            );
                        }
                    }

                    $offset += $batchSize;
                } while (\count($rows) === $batchSize);

                // 3. Drop stdout / stderr columns from job
                $jobTable
                    ->removeColumn('stdout')
                    ->removeColumn('stderr')
                    ->save();
            }
        }
    }

    public function down(): void
    {
        $adapter = $this->getAdapter()->getAdapterType();

        // 1. Re-add stdout / stderr columns to job
        $jobTable = $this->table('job');

        if (!$jobTable->hasColumn('stdout')) {
            if ($adapter === 'mysql') {
                $jobTable
                    ->addColumn('stdout', 'blob', ['null' => false, 'default' => '', 'limit' => \Phinx\Db\Adapter\MysqlAdapter::BLOB_LONG])
                    ->addColumn('stderr', 'blob', ['null' => false, 'default' => '', 'limit' => \Phinx\Db\Adapter\MysqlAdapter::BLOB_LONG])
                    ->save();
            } else {
                $jobTable
                    ->addColumn('stdout', 'text', ['null' => false, 'default' => ''])
                    ->addColumn('stderr', 'text', ['null' => false, 'default' => ''])
                    ->save();
            }
        }

        // 2. Reconstruct stdout / stderr from job_output_lines
        if ($adapter === 'mysql') {
            $this->execute("
                UPDATE job j
                JOIN (
                    SELECT job_id,
                           GROUP_CONCAT(CASE WHEN type = 'stdout' THEN line ELSE NULL END ORDER BY seq SEPARATOR '\n') AS stdout,
                           GROUP_CONCAT(CASE WHEN type = 'stderr' THEN line ELSE NULL END ORDER BY seq SEPARATOR '\n') AS stderr
                    FROM job_output_lines
                    GROUP BY job_id
                ) ol ON j.id = ol.job_id
                SET j.stdout = COALESCE(ol.stdout, ''),
                    j.stderr = COALESCE(ol.stderr, '')
            ");
        } else {
            // SQLite / PostgreSQL: row-by-row reconstruction
            $rows = $this->fetchAll('SELECT DISTINCT job_id FROM job_output_lines');

            foreach ($rows as $row) {
                $stdoutLines = $this->fetchAll(
                    "SELECT line FROM job_output_lines WHERE job_id = {$row['job_id']} AND type = 'stdout' ORDER BY seq",
                );
                $stderrLines = $this->fetchAll(
                    "SELECT line FROM job_output_lines WHERE job_id = {$row['job_id']} AND type = 'stderr' ORDER BY seq",
                );

                $stdout = str_replace("'", "''", implode("\n", array_column($stdoutLines, 'line')));
                $stderr = str_replace("'", "''", implode("\n", array_column($stderrLines, 'line')));

                $this->execute("UPDATE job SET stdout = '{$stdout}', stderr = '{$stderr}' WHERE id = {$row['job_id']}");
            }
        }

        // 3. Drop job_output_lines table
        $this->table('job_output_lines')->drop()->save();
    }
}
