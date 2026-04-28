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

final class UserLoginUnique extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('user');

        // Remove the old composite unique index on (login, email)
        if ($table->hasIndex(['login', 'email'])) {
            $table->removeIndex(['login', 'email'])->save();
        }

        // Deduplicate: keep the row with the lowest id for each login, delete the rest.
        // MySQL cannot reference the target table directly in DELETE...NOT IN, requiring a
        // derived table wrapper. PostgreSQL and SQLite do not have this restriction and do
        // not accept backtick quoting, so we branch on adapter type.
        $adapter = $this->getAdapter()->getAdapterType();

        if ($adapter === 'mysql') {
            $this->execute(
                'DELETE FROM `user` WHERE id NOT IN (SELECT min_id FROM (SELECT MIN(id) AS min_id FROM `user` GROUP BY login) AS t)'
            );
        } else {
            $this->execute(
                'DELETE FROM "user" WHERE id NOT IN (SELECT MIN(id) FROM "user" GROUP BY login)'
            );
        }

        // Add a unique index on login alone
        if (!$table->hasIndex(['login'])) {
            $table->addIndex(['login'], ['unique' => true, 'name' => 'user_login_unique'])->save();
        }
    }

    public function down(): void
    {
        $table = $this->table('user');

        if ($table->hasIndex(['login'])) {
            $table->removeIndex(['login'])->save();
        }

        if (!$table->hasIndex(['login', 'email'])) {
            $table->addIndex(['login', 'email'], ['unique' => true])->save();
        }
    }
}
