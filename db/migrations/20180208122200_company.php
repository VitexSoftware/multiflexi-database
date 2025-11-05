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

class Company extends AbstractMigration
{
    public function change(): void
    {
        // Company table - Stores information about companies/clients using the system
        $table = $this->table('company', ['comment' => 'Companies/clients using the system with their AbraFlexi configurations']);
        $table
            ->addColumn('enabled', 'boolean', ['default' => false, 'comment' => 'Company account status - whether company can use services'])
            ->addColumn('settings', 'text', ['null' => true, 'comment' => 'JSON serialized company-specific configuration'])
            ->addColumn('logo', 'text', ['null' => true, 'comment' => 'Company logo image data (base64 encoded)'])
            ->addColumn('abraflexi', 'integer', ['limit' => 128, 'comment' => 'Foreign key to abraflexis table - which AbraFlexi instance this company uses'])
            ->addColumn('nazev', 'string', ['null' => true, 'limit' => 32, 'comment' => 'Company name in Czech (nazev = name)'])
            ->addColumn('ic', 'string', ['null' => true, 'limit' => 32, 'comment' => 'Company identification number (IČ in Czech)'])
            ->addColumn('company', 'string', ['comment' => 'Unique company identifier/code for system references'])
            ->addColumn('rw', 'boolean', ['comment' => 'Write permissions - whether this company has write access to modify data'])
            ->addColumn('setup', 'boolean', ['comment' => 'Flag indicating if initial company setup is completed'])
            ->addColumn('webhook', 'boolean', ['comment' => 'Flag indicating if webhook integration is configured'])
            ->addColumn('DatCreate', 'datetime', ['comment' => 'Timestamp when company record was created'])
            ->addColumn('DatUpdate', 'datetime', ['null' => true, 'comment' => 'Last modification timestamp'])
            ->addIndex(['abraflexi', 'company'], ['unique' => true])             // Ensure unique company code per AbraFlexi instance
            ->create();

        //        if ($this->adapter->getAdapterType() != 'sqlite') {
        //            $table
        //                    ->changeColumn('id', 'integer', ['identity' => true])
        //                    ->save();
        //        }
    }
}
