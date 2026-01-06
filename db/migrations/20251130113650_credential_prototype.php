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

final class CredentialPrototype extends AbstractMigration
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
        $table = $this->table('credential_prototype');
        $table->addColumn('uuid', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('code', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('version', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('url', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('logo', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['uuid'], ['unique' => true, 'name' => 'idx_credential_prototype_uuid'])
            ->addIndex(['code'], ['unique' => true, 'name' => 'idx_credential_prototype_code'])
            ->addColumn('created_at', 'datetime', ['comment' => 'Timestamp when customer account was created'])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'comment' => 'Last modification timestamp'])
            ->addIndex(
                ['uuid', 'version'],
                ['name' => 'idx_credential_prototype_version', 'unique' => false],
            )
            ->create();

        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

        // Create the credential_prototype_field table
        $table = $this->table('credential_prototype_field');
        $table->addColumn('credential_prototype_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('keyword', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('type', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('hint', 'string', ['limit' => 256, 'null' => true])
            ->addColumn('default_value', 'string', ['limit' => 256, 'null' => true])
            ->addColumn('required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('options', 'text', ['null' => true])
            ->addIndex(['credential_prototype_id', 'keyword'], ['unique' => true, 'name' => 'idx_credprototype_field_unique'])
            ->addForeignKey('credential_prototype_id', 'credential_prototype', 'id', ['constraint' => 'fk_credprototype_field', 'delete' => 'CASCADE'])
            ->create();

        // Create credential_prototype_translations table
        $credentialTypeTranslations = $this->table('credential_prototype_translations');
        $credentialTypeTranslations
            ->addColumn('credential_prototype_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addIndex(['credential_prototype_id', 'lang'], ['unique' => true, 'name' => 'idx_credprototype_lang'])
            ->addForeignKey('credential_prototype_id', 'credential_prototype', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // Create credential_prototype_field_translations table
        $credentialTypeFieldTranslations = $this->table('credential_prototype_field_translations');
        $credentialTypeFieldTranslations
            ->addColumn('credential_prototype_field_id', 'integer', array_merge(['null' => false], $unsigned))
            ->addColumn('lang', 'string', ['limit' => 5, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addColumn('hint', 'text', ['null' => true, 'default' => null])
            ->addIndex(['credential_prototype_field_id', 'lang'], ['unique' => true, 'name' => 'idx_credprototype_field_lang'])
            ->addForeignKey('credential_prototype_field_id', 'credential_prototype_field', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
