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

final class ConffieldOptions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('conffield');

        if (!$table->hasColumn('options')) {
            $table->addColumn('options', 'text', [
                'null' => true,
                'after' => 'defval',
                'comment' => 'JSON map of allowed values (value => label) for fields of type set',
            ]);
        }

        $table->save();
    }
}
