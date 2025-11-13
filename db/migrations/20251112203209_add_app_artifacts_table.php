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

final class AddAppArtifactsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $table = $this->table(
            'app_artifacts',
            [
                'comment' => 'Artifacts (files, binaries, configs) associated with each application for deployment and management',
            ],
        );
        $table
            ->addColumn(
                'app_id',
                'integer',
                array_merge(['null' => false, 'comment' => 'Foreign key to apps table - identifies which application this artifact belongs to'], $unsigned),
            )
            ->addColumn('path', 'string', [
                'limit' => 512,
                'null' => false,
                'comment' => 'Filesystem path or storage location of the artifact',
            ])
            ->addColumn('type', 'string', [
                'limit' => 128,
                'null' => false,
                'comment' => 'Type of artifact (binary, config, script, image, etc.)',
            ])
            ->addColumn('created', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp when the artifact record was created',
            ])
            ->addForeignKey('app_id', 'apps', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();

        $cfgTranslations = $this->table('app_artifact_translations');
        $cfgTranslations
            ->addColumn('app_artifact_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addColumn('hint', 'text', ['null' => true, 'default' => null])
            ->addIndex(['app_artifact_id', 'lang'], ['unique' => true, 'name' => 'idxx_conf_lang'])
            ->addForeignKey('app_artifact_id', 'app_artifacts', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
