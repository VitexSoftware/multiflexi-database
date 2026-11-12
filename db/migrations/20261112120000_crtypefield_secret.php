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
 * Let the credential type editor mark a custom field as sensitive.
 */
final class CrtypefieldSecret extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('crtypefield');

        if (!$table->hasColumn('secret')) {
            $table->addColumn('secret', 'boolean', ['null' => false, 'default' => false, 'after' => 'required', 'comment' => 'Whether this field contains sensitive content (passwords, tokens, etc.)'])->update();
        }
    }
}
