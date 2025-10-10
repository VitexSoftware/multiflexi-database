<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTranslations extends AbstractMigration
{
    public function change(): void
    {
        // === CREATE TRANSLATION TABLES ===

        // apps translations
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        if (!$this->hasTable('app_translations')) {
            $appTranslations = $this->table('app_translations');
            $appTranslations
                  ->addColumn('app_id', 'integer', ['null' => false, 'signed' => true])
                  ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
                  ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                  ->addColumn('title', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                  ->addColumn('description', 'text', ['null' => true, 'default' => null])
                  ->addIndex(['app_id', 'lang'], ['unique' => true, 'name' => 'idx_app_lang'])
                  ->addForeignKey('app_id', 'apps', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->create();
        }

        // configuration translations
        if (!$this->hasTable('configuration_translations')) {
            $cfgTranslations = $this->table('configuration_translations');
            $cfgTranslations
                  ->addColumn('configuration_id', 'integer', ['null' => false, 'signed' => true])
                  ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
                  ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                  ->addColumn('description', 'text', ['null' => true, 'default' => null])
                  ->addColumn('hint', 'text', ['null' => true, 'default' => null])
                  ->addIndex(['configuration_id','lang'], ['unique' => true, 'name' => 'idx_conf_lang'])
                  ->addForeignKey('configuration_id', 'configuration', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->create();
        }
    }

}
