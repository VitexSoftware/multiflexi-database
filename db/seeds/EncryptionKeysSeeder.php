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

use Phinx\Seed\AbstractSeed;

/**
 * EncryptionKeysSeeder – initialises the encryption_keys table on first install.
 *
 * For each of the three standard keys (default, credentials, personal_data) it:
 *   - skips the row when key_data already contains a real (non-placeholder) value
 *   - generates a fresh 256-bit key, encrypts it with ENCRYPTION_MASTER_KEY and
 *     stores the result in the format expected by MultiFlexi\Security\DataEncryption:
 *       base64( 16-byte-IV || AES-256-CBC( rawKey, sha256(masterKey), IV ) )
 *
 * Requires ENCRYPTION_MASTER_KEY (or MULTIFLEXI_MASTER_KEY) to be available as
 * an environment variable or via the Ease config file loaded by phinx-adapter.php.
 *
 * Safe to re-run: rows with a real key_data value are never overwritten.
 */
class EncryptionKeysSeeder extends AbstractSeed
{
    private const PLACEHOLDER = 'PLACEHOLDER_KEY_TO_BE_REPLACED';

    /**
     * The three standard keys used by MultiFlexi\Security\DataEncryption.
     */
    private const KEY_NAMES = [
        'default'       => 'AES-256-GCM',
        'credentials'   => 'AES-256-GCM',
        'personal_data' => 'AES-256-GCM',
    ];

    public function run(): void
    {
        $masterKey = $this->resolveMasterKey();

        if ($masterKey === null) {
            $this->output->writeln(
                '<error>EncryptionKeysSeeder: ENCRYPTION_MASTER_KEY is not set. '
                . 'Set it in .env (ENCRYPTION_MASTER_KEY=…) and re-run: '
                . 'phinx seed:run -s EncryptionKeysSeeder</error>'
            );

            return;
        }

        // Derive the 32-byte hashed key used by DataEncryption::getMasterKey()
        $hashedMasterKey = hash('sha256', $masterKey, true);

        foreach (self::KEY_NAMES as $keyName => $algorithm) {
            $this->ensureKey($keyName, $algorithm, $hashedMasterKey);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Ensure a single key row exists and is not a placeholder.
     *
     * @param string $keyName         Name stored in the key_name column
     * @param string $algorithm       Algorithm label (e.g. AES-256-GCM)
     * @param string $hashedMasterKey 32-byte binary key (sha256 of master secret)
     */
    private function ensureKey(string $keyName, string $algorithm, string $hashedMasterKey): void
    {
        $table   = $this->table('encryption_keys');
        $adapter = $this->getAdapter();

        // Fetch current row via the Phinx adapter (DB-agnostic)
        $rows = $adapter->fetchAll(
            sprintf(
                "SELECT id, key_data FROM encryption_keys WHERE key_name = '%s' LIMIT 1",
                addslashes($keyName)
            )
        );

        $existingRow = $rows[0] ?? null;
        $currentData = $existingRow['key_data'] ?? null;

        // Already contains a real key – leave it alone
        if ($currentData !== null && $currentData !== self::PLACEHOLDER && $currentData !== '') {
            $this->output->writeln(
                sprintf('<info>EncryptionKeysSeeder: key "%s" already initialised – skipping.</info>', $keyName)
            );

            return;
        }

        // Generate new raw 256-bit (32-byte) key
        $rawKey      = random_bytes(32);
        $iv          = random_bytes(16);
        $encrypted   = openssl_encrypt($rawKey, 'aes-256-cbc', $hashedMasterKey, \OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            $this->output->writeln(
                sprintf('<error>EncryptionKeysSeeder: openssl_encrypt failed for key "%s": %s</error>',
                    $keyName, openssl_error_string())
            );

            return;
        }

        $keyData = base64_encode($iv . $encrypted);
        $now     = date('Y-m-d H:i:s');

        if ($existingRow !== null) {
            // Row exists but has a placeholder – update it in place
            $adapter->execute(
                sprintf(
                    "UPDATE encryption_keys SET key_data = '%s', algorithm = '%s', rotated_at = '%s' WHERE key_name = '%s'",
                    addslashes($keyData),
                    addslashes($algorithm),
                    $now,
                    addslashes($keyName)
                )
            );
            $this->output->writeln(
                sprintf('<info>EncryptionKeysSeeder: replaced placeholder for key "%s".</info>', $keyName)
            );
        } else {
            // Row does not exist yet – insert it
            $table->insert([
                'key_name'   => $keyName,
                'key_data'   => $keyData,
                'algorithm'  => $algorithm,
                'created_at' => $now,
                'is_active'  => true,
            ])->saveData();
            $this->output->writeln(
                sprintf('<info>EncryptionKeysSeeder: created new key "%s".</info>', $keyName)
            );
        }
    }

    /**
     * Return the raw (un-hashed) master key string from environment / config,
     * or null when no master key is available.
     */
    private function resolveMasterKey(): ?string
    {
        $masterKey = getenv('ENCRYPTION_MASTER_KEY');

        if ($masterKey !== false && $masterKey !== '') {
            return $masterKey;
        }

        $masterKey = getenv('MULTIFLEXI_MASTER_KEY');

        if ($masterKey !== false && $masterKey !== '') {
            return $masterKey;
        }

        // Attempt to read from Ease config if the autoloader is present
        if (class_exists(\Ease\Shared::class)) {
            $masterKey = \Ease\Shared::cfg('ENCRYPTION_MASTER_KEY');

            if ($masterKey) {
                return (string) $masterKey;
            }
        }

        return null;
    }
}
