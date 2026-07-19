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
 * Prepares `credata` (the table holding actual per-field Credential values,
 * including passwords/tokens/API keys) for encryption at rest.
 *
 * `value` is widened from string(255) to text because the JSON-encoded
 * encryption envelope (base64 ciphertext + iv + tag + algorithm + key_name
 * + key_version + encrypted_at) does not fit in 255 characters for anything
 * but very short secrets.
 *
 * This targets `credata` directly rather than the `encrypted_data`/
 * `encryption_key_id` columns added to `credentials` by the GDPR phase 3
 * migration: that migration guards on hasTable('credential') (singular),
 * which never matches the real table name `credentials` (plural), so those
 * columns were never actually created on any real table.
 */
final class CredataEncryptionAtRest extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('credata')) {
            return;
        }

        $table = $this->table('credata');
        $table->changeColumn('value', 'text', ['null' => true]);

        if (!$table->hasColumn('is_encrypted')) {
            $table->addColumn('is_encrypted', 'boolean', ['null' => false, 'default' => false, 'after' => 'value']);
        }

        if (!$table->hasColumn('encryption_key_version')) {
            $table->addColumn('encryption_key_version', 'integer', ['null' => true, 'after' => 'is_encrypted']);
        }

        $table->save();
    }

    public function down(): void
    {
        if (!$this->hasTable('credata')) {
            return;
        }

        $table = $this->table('credata');

        if ($table->hasColumn('encryption_key_version')) {
            $table->removeColumn('encryption_key_version');
        }

        if ($table->hasColumn('is_encrypted')) {
            $table->removeColumn('is_encrypted');
        }

        $table->changeColumn('value', 'string', ['limit' => 255, 'null' => true]);
        $table->save();
    }
}
