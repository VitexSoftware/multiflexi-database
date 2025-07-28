<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CompanyNameOnly extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Remove the 'company' column from the 'company' table.
     */
    public function change(): void
    {
        $table = $this->table('company');
        if ($table->hasColumn('company')) {
            $table->removeColumn('company')->update();
        }
    }
}
