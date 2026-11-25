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
 * Make the "enabled" flag of applications and companies usable.
 *
 * Until now it was ignored by the scheduler and reset to 0 by every manifest
 * import, so its stored value says nothing about what the administrator wants.
 * Everything that has been running keeps running:
 *  - all applications become enabled,
 *  - disabled companies with a job in the last 30 days become enabled,
 *  - other disabled companies stay disabled and are listed.
 * New records are enabled by default.
 */
final class EnabledByDefault extends AbstractMigration
{
    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() === 'pgsql') {
            [$on, $off] = ['true', 'false'];
        } else {
            [$on, $off] = ['1', '0'];
        }

        $apps = $this->execute("UPDATE apps SET enabled = {$on} WHERE enabled = {$off}");
        $since = (new \DateTime('-30 days'))->format('Y-m-d H:i:s');
        $companies = $this->execute("UPDATE company SET enabled = {$on} WHERE enabled = {$off} AND id IN (SELECT company_id FROM job WHERE schedule > '{$since}' AND company_id IS NOT NULL)");

        $this->getOutput()->writeln(sprintf('Enabled %d applications and %d companies', $apps, $companies));

        foreach ($this->fetchAll("SELECT id, name FROM company WHERE enabled = {$off}") as $row) {
            $this->getOutput()->writeln(sprintf('Company #%d %s stays disabled', $row['id'], $row['name']));
        }

        $this->setDefault(true);
    }

    public function down(): void
    {
        $this->setDefault(false);
    }

    private function setDefault(bool $default): void
    {
        // SQLite cannot alter a column in place; new records get the value from the core library
        if ($this->getAdapter()->getAdapterType() === 'sqlite') {
            return;
        }

        $this->table('apps')->changeColumn('enabled', 'boolean', ['null' => false, 'default' => $default, 'comment' => 'Whether this application is started by the scheduler and events'])->save();
        $this->table('company')->changeColumn('enabled', 'boolean', ['null' => false, 'default' => $default, 'comment' => 'Disabled company runs no jobs and is not billed'])->save();
    }
}
