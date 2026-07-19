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
 * Adds key versioning to encryption_keys so rotating a key no longer
 * overwrites (and thereby destroys) the key material used to encrypt
 * previously-stored data. Before this migration, key_name was unique and
 * generateKey()/rotateKey() overwrote key_data in place, permanently
 * breaking decryption of anything encrypted under a rotated key.
 */
final class EncryptionKeyVersioning extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('encryption_keys')) {
            return;
        }

        $table = $this->table('encryption_keys');

        if (!$table->hasColumn('version')) {
            $table->addColumn('version', 'integer', ['null' => false, 'default' => 1, 'after' => 'key_name'])->save();
        }

        if ($table->hasIndex(['key_name'])) {
            $table->removeIndex(['key_name'])->save();
        }

        $table = $this->table('encryption_keys');
        $table->addIndex(['key_name', 'version'], ['unique' => true])->save();
    }

    public function down(): void
    {
        if (!$this->hasTable('encryption_keys')) {
            return;
        }

        $table = $this->table('encryption_keys');

        if ($table->hasIndex(['key_name', 'version'])) {
            $table->removeIndex(['key_name', 'version'])->save();
        }

        $table = $this->table('encryption_keys');
        $table->addIndex(['key_name'], ['unique' => true])->save();

        if ($table->hasColumn('version')) {
            $table->removeColumn('version')->save();
        }
    }
}
