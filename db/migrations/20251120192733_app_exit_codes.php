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

final class AppExitCodes extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Create app_exit_codes table to store localized descriptions of application exit codes.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('app_exit_codes');
        $table
            ->addColumn('app_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'Reference to the application',
            ])
            ->addColumn('exit_code', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'Exit code number (0-255)',
            ])
            ->addColumn('lang', 'string', [
                'limit' => 5,
                'null' => false,
                'comment' => 'Language code (en, cs, etc.)',
            ])
            ->addColumn('description', 'text', [
                'null' => false,
                'comment' => 'Localized description of the exit code meaning',
            ])
            ->addColumn('severity', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'error',
                'comment' => 'Severity level: success, info, warning, error, critical',
            ])
            ->addColumn('retry', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Whether to retry job on this exit code',
            ])
            ->addColumn('DatCreate', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Record creation timestamp',
            ])
            ->addColumn('DatSave', 'datetime', [
                'null' => true,
                'comment' => 'Last modification timestamp',
            ])
            ->addIndex(['app_id'])
            ->addIndex(['app_id', 'exit_code'])
            ->addIndex(['app_id', 'exit_code', 'lang'], ['unique' => true])
            ->addForeignKey('app_id', 'apps', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }
}
