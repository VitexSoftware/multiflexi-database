<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RunTemplateNote extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('runtemplate');
        $table->addColumn('note', 'text', [
            'null' => true,
            'comment' => 'Additional notes or comments about this run template'
        ]);
        
        // Add fulltext index for databases that support it
        $adapterType = $this->getAdapter()->getAdapterType();
        if ($adapterType === 'mysql' || $adapterType === 'pgsql') {
            $table->addIndex('note', ['type' => 'fulltext']);
        }
        
        $table->update();
    }
}
