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

final class ConffieldExtendedProperties extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('conffield');

        if (!$table->hasColumn('hint')) {
            $table->addColumn('hint', 'string', ['length' => 1024, 'null' => false, 'default' => '', 'after' => 'description', 'comment' => 'Help text or tooltip shown to users when configuring this field']);
        }

        if (!$table->hasColumn('note')) {
            $table->addColumn('note', 'string', ['length' => 1024, 'null' => false, 'default' => '', 'after' => 'hint', 'comment' => 'Additional notes or remarks about this configuration field']);
        }

        if (!$table->hasColumn('secret')) {
            $table->addColumn('secret', 'boolean', ['null' => false, 'default' => false, 'after' => 'required', 'comment' => 'Whether this field contains sensitive content (passwords, tokens, etc.)']);
        }

        if (!$table->hasColumn('multiline')) {
            $table->addColumn('multiline', 'boolean', ['null' => false, 'default' => false, 'after' => 'secret', 'comment' => 'Whether this field should use a textarea for multi-line input']);
        }

        if (!$table->hasColumn('expiring')) {
            $table->addColumn('expiring', 'boolean', ['null' => false, 'default' => false, 'after' => 'multiline', 'comment' => 'Whether this field value has an expiration (e.g., tokens, certificates)']);
        }

        $table->save();
    }
}
