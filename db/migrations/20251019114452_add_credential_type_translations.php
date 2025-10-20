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

final class AddCredentialTypeTranslations extends AbstractMigration
{
    public function change(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // Create credential_type_translations table
        $credentialTypeTranslations = $this->table('credential_type_translations');
        $credentialTypeTranslations
            ->addColumn('credential_type_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addIndex(['credential_type_id', 'lang'], ['unique' => true, 'name' => 'idx_credtype_lang'])
            ->addForeignKey('credential_type_id', 'credential_type', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Create credential_type_field_translations table  
        $credentialTypeFieldTranslations = $this->table('credential_type_field_translations');
        $credentialTypeFieldTranslations
            ->addColumn('crtypefield_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addColumn('hint', 'text', ['null' => true, 'default' => null])
            ->addIndex(['crtypefield_id', 'lang'], ['unique' => true, 'name' => 'idx_crtypefield_lang'])
            ->addForeignKey('crtypefield_id', 'crtypefield', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
