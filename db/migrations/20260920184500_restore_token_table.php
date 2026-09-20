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
 * Recreate the API bearer `token` table when it is missing.
 *
 * The original migration (20221012185432) is still marked applied in phinxlog on
 * some installs, but the table itself was dropped (no reverse migration). CLI
 * `token:list` / TUI Tokens then fail with "Table '…token' doesn't exist".
 */
final class RestoreTokenTable extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('token')) {
            return;
        }

        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table('token', [
            'comment' => 'Authentication tokens for API access and session management',
        ]);
        $table
            ->addColumn('token', 'string', [
                'limit' => 64,
                'comment' => 'Authentication token string (usually random hash)',
            ])
            ->addColumn('start', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Token creation/activation timestamp',
            ])
            ->addColumn('until', 'datetime', [
                'null' => true,
                'comment' => 'Token expiration timestamp (null = never expires)',
            ])
            ->addColumn('user_id', 'integer', array_merge([
                'null' => false,
                'comment' => 'Foreign key to user table - which user owns this token',
            ], $unsigned))
            ->addIndex(['token'], ['unique' => true, 'name' => 'token_token_unique'])
            ->addIndex(['user_id'], ['name' => 'idx_token_user_id'])
            ->addForeignKey('user_id', 'user', 'id', [
                'constraint' => 'token_user_must_exist',
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('token')) {
            $this->table('token')->drop()->save();
        }
    }
}
