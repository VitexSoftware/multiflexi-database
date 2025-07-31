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

final class CompanyNameOnly extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Remove the 'company' column and the 'abraflexi' index from the 'company' table.
     */
    public function change(): void
    {
        $table = $this->table('company');

        // Remove the composite index 'abraflexi' (on server, company)
        if ($table->hasIndex(['server', 'company'])) {
            $table->removeIndex(['server', 'company']);
        }

        $table->removeColumn('company')->update();
    }
}
