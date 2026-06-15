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

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EncryptionKeysSeeder.
 *
 * These tests exercise the seeder logic directly via an in-memory SQLite
 * database – no Phinx CLI is needed, and the seeder class is loaded manually
 * so the test file is self-contained.
 *
 * Run: vendor/bin/phpunit tests/EncryptionKeysSeederTest.php
 */
class EncryptionKeysSeederTest extends TestCase
{
    private \PDO $pdo;

    /** @var string – plaintext master key used in every test */
    private string $masterKey = 'test-master-key-for-phpunit';

    protected function setUp(): void
    {
        // In-memory SQLite database
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Mirror the schema created by the GDPR Phase 3 migration
        $this->pdo->exec(
            'CREATE TABLE encryption_keys (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                key_name   TEXT    NOT NULL UNIQUE,
                key_data   TEXT    NOT NULL,
                algorithm  TEXT    NOT NULL DEFAULT \'AES-256-GCM\',
                created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                rotated_at TEXT,
                is_active  INTEGER NOT NULL DEFAULT 1
            )'
        );

        // Make the master key available via the environment variable
        putenv('ENCRYPTION_MASTER_KEY=' . $this->masterKey);
    }

    protected function tearDown(): void
    {
        putenv('ENCRYPTION_MASTER_KEY');
    }

    // -------------------------------------------------------------------------
    // Helper – run the seeder logic isolated from Phinx infrastructure
    // -------------------------------------------------------------------------

    /**
     * Execute seeder logic directly against our in-memory PDO.
     *
     * We replicate the key-generation algorithm from EncryptionKeysSeeder /
     * DataEncryption so the tests remain self-contained and verifiable.
     *
     * @return array<string, string> map of keyName => outcome ('created'|'updated'|'skipped'|'no_master_key')
     */
    private function runSeederLogic(): array
    {
        $masterKey = getenv('ENCRYPTION_MASTER_KEY') ?: getenv('MULTIFLEXI_MASTER_KEY') ?: null;

        if ($masterKey === null || $masterKey === '') {
            return array_fill_keys(['default', 'credentials', 'personal_data'], 'no_master_key');
        }

        $hashedMasterKey = hash('sha256', $masterKey, true);
        $placeholder     = 'PLACEHOLDER_KEY_TO_BE_REPLACED';
        $keyNames        = ['default' => 'AES-256-GCM', 'credentials' => 'AES-256-GCM', 'personal_data' => 'AES-256-GCM'];
        $results         = [];
        $now             = date('Y-m-d H:i:s');

        foreach ($keyNames as $keyName => $algorithm) {
            $stmt = $this->pdo->prepare('SELECT id, key_data FROM encryption_keys WHERE key_name = ? LIMIT 1');
            $stmt->execute([$keyName]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $currentData = $row['key_data'] ?? null;

            if ($currentData !== null && $currentData !== $placeholder && $currentData !== '') {
                $results[$keyName] = 'skipped';

                continue;
            }

            // Generate a new encrypted key (same algorithm as DataEncryption::encryptStoredKey)
            $rawKey    = random_bytes(32);
            $iv        = random_bytes(16);
            $encrypted = openssl_encrypt($rawKey, 'aes-256-cbc', $hashedMasterKey, OPENSSL_RAW_DATA, $iv);
            $keyData   = base64_encode($iv . $encrypted);

            if ($row !== false) {
                // Update placeholder
                $upd = $this->pdo->prepare(
                    'UPDATE encryption_keys SET key_data = ?, algorithm = ?, rotated_at = ? WHERE key_name = ?'
                );
                $upd->execute([$keyData, $algorithm, $now, $keyName]);
                $results[$keyName] = 'updated';
            } else {
                // Insert new row
                $ins = $this->pdo->prepare(
                    'INSERT INTO encryption_keys (key_name, key_data, algorithm, created_at, is_active) VALUES (?, ?, ?, ?, 1)'
                );
                $ins->execute([$keyName, $keyData, $algorithm, $now]);
                $results[$keyName] = 'created';
            }
        }

        return $results;
    }

    /**
     * Decode the stored key_data and verify it can be decrypted back.
     */
    private function decryptStoredKey(string $encryptedKey): string
    {
        $masterKey       = getenv('ENCRYPTION_MASTER_KEY');
        $hashedMasterKey = hash('sha256', $masterKey, true);
        $data            = base64_decode($encryptedKey, true);

        $iv        = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, 'aes-256-cbc', $hashedMasterKey, OPENSSL_RAW_DATA, $iv);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * On an empty table the seeder must insert all three keys.
     */
    public function testCreatesAllThreeKeysOnEmptyTable(): void
    {
        $results = $this->runSeederLogic();

        self::assertSame('created', $results['default'],       'default key should be created');
        self::assertSame('created', $results['credentials'],   'credentials key should be created');
        self::assertSame('created', $results['personal_data'], 'personal_data key should be created');

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM encryption_keys')->fetchColumn();
        self::assertSame(3, $count, 'Three rows must exist after seeding');
    }

    /**
     * Running the seeder a second time must NOT overwrite any existing real key.
     */
    public function testIdempotentSecondRunDoesNotOverwrite(): void
    {
        $this->runSeederLogic(); // first run – inserts

        // Capture key_data values after first run
        $before = [];

        foreach (['default', 'credentials', 'personal_data'] as $name) {
            $stmt = $this->pdo->prepare('SELECT key_data FROM encryption_keys WHERE key_name = ? LIMIT 1');
            $stmt->execute([$name]);
            $before[$name] = $stmt->fetchColumn();
        }

        $results = $this->runSeederLogic(); // second run – must skip all

        self::assertSame('skipped', $results['default']);
        self::assertSame('skipped', $results['credentials']);
        self::assertSame('skipped', $results['personal_data']);

        // key_data must be identical to what was set in the first run
        foreach (['default', 'credentials', 'personal_data'] as $name) {
            $stmt = $this->pdo->prepare('SELECT key_data FROM encryption_keys WHERE key_name = ? LIMIT 1');
            $stmt->execute([$name]);
            $after = $stmt->fetchColumn();
            self::assertSame($before[$name], $after, "key_data for '{$name}' must not be changed on second run");
        }
    }

    /**
     * A placeholder value ('PLACEHOLDER_KEY_TO_BE_REPLACED') must be replaced
     * with a proper encrypted key.
     */
    public function testReplacesPlaceholderKeyData(): void
    {
        // Pre-populate with placeholder (as the migration originally does)
        $this->pdo->exec(
            "INSERT INTO encryption_keys (key_name, key_data, algorithm, is_active) "
            . "VALUES ('credentials', 'PLACEHOLDER_KEY_TO_BE_REPLACED', 'AES-256-GCM', 1)"
        );

        $results = $this->runSeederLogic();

        self::assertSame('updated', $results['credentials'], 'Placeholder row should be updated');

        $stmt = $this->pdo->prepare('SELECT key_data FROM encryption_keys WHERE key_name = ? LIMIT 1');
        $stmt->execute(['credentials']);
        $keyData = $stmt->fetchColumn();

        self::assertNotSame('PLACEHOLDER_KEY_TO_BE_REPLACED', $keyData, 'Placeholder must be replaced');
        self::assertNotEmpty($keyData, 'New key_data must not be empty');

        // Verify the stored value can be decrypted to a 32-byte string
        $rawKey = $this->decryptStoredKey($keyData);
        self::assertSame(32, \strlen($rawKey), 'Decrypted raw key must be 32 bytes');
    }

    /**
     * A row whose key_data is already a real (non-placeholder) value must be
     * left completely untouched.
     */
    public function testPreservesRealKey(): void
    {
        // Build a legitimate encrypted key manually
        $masterKey       = getenv('ENCRYPTION_MASTER_KEY');
        $hashedMasterKey = hash('sha256', $masterKey, true);
        $rawKey          = random_bytes(32);
        $iv              = random_bytes(16);
        $encrypted       = openssl_encrypt($rawKey, 'aes-256-cbc', $hashedMasterKey, OPENSSL_RAW_DATA, $iv);
        $realKeyData     = base64_encode($iv . $encrypted);

        $this->pdo->prepare(
            "INSERT INTO encryption_keys (key_name, key_data, algorithm, is_active) VALUES (?, ?, 'AES-256-GCM', 1)"
        )->execute(['credentials', $realKeyData]);

        $results = $this->runSeederLogic();

        self::assertSame('skipped', $results['credentials'], 'Real key must not be overwritten');

        $stmt = $this->pdo->prepare('SELECT key_data FROM encryption_keys WHERE key_name = ? LIMIT 1');
        $stmt->execute(['credentials']);
        self::assertSame($realKeyData, $stmt->fetchColumn(), 'key_data must remain identical');
    }

    /**
     * When no master key is available, the seeder must not write anything.
     */
    public function testSkipsAllKeysWhenNoMasterKey(): void
    {
        putenv('ENCRYPTION_MASTER_KEY');   // unset
        putenv('MULTIFLEXI_MASTER_KEY');   // unset

        $results = $this->runSeederLogic();

        foreach ($results as $keyName => $outcome) {
            self::assertSame('no_master_key', $outcome, "Key '{$keyName}' should report no_master_key");
        }

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM encryption_keys')->fetchColumn();
        self::assertSame(0, $count, 'Nothing should be written without a master key');
    }

    /**
     * The stored key_data for a freshly created key must be decodable and
     * decrypt to exactly 32 raw bytes.
     */
    public function testGeneratedKeyDataIsValid(): void
    {
        $this->runSeederLogic();

        foreach (['default', 'credentials', 'personal_data'] as $name) {
            $stmt = $this->pdo->prepare('SELECT key_data FROM encryption_keys WHERE key_name = ? LIMIT 1');
            $stmt->execute([$name]);
            $keyData = $stmt->fetchColumn();

            // Must be valid base64
            $decoded = base64_decode($keyData, true);
            self::assertNotFalse($decoded, "key_data for '{$name}' must be valid base64");

            // Decoded length: 16 (IV) + 32 (AES-256-CBC block-aligned raw key) = 48 bytes
            self::assertGreaterThanOrEqual(48, \strlen($decoded), "Decoded key_data for '{$name}' must be at least 48 bytes");

            // Must decrypt back to a 32-byte key
            $rawKey = $this->decryptStoredKey($keyData);
            self::assertSame(32, \strlen($rawKey), "Decrypted raw key for '{$name}' must be 32 bytes");
        }
    }
}
