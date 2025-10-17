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

final class AddTranslations extends AbstractMigration
{
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        $appTranslations = $this->table('app_translations');
        $appTranslations
            ->addColumn('app_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addIndex(['app_id', 'lang'], ['unique' => true, 'name' => 'idx_app_lang'])
            ->addForeignKey('app_id', 'apps', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $cfgTranslations = $this->table('configuration_translations');
        $cfgTranslations
            ->addColumn('configuration_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addColumn('hint', 'text', ['null' => true, 'default' => null])
            ->addIndex(['configuration_id', 'lang'], ['unique' => true, 'name' => 'idx_conf_lang'])
            ->addForeignKey('configuration_id', 'configuration', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
