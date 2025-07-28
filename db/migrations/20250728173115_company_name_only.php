<?php

declare(strict_types=1);

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
