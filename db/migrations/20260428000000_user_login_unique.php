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
