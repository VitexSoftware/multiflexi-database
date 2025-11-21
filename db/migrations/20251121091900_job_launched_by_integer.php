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

final class JobLaunchedByInteger extends AbstractMigration
{
    /**
     * Convert launched_by from text to integer and add foreign key to user table.
     * 
     * This migration converts the launched_by column to properly reference the user table.
     * All jobs must have a valid user (web or CLI thanks to UnixUser auto-creation).
     */
    public function up(): void
    {
        $databaseType = $this->getAdapter()->getOption('adapter');
        $unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];
        
        $table = $this->table('job');
        
        // First, ensure all NULL values are set to a valid user ID
        // We'll use user ID 1 as default (typically admin) for old records
        if ($databaseType === 'mysql') {
            $this->execute("
                UPDATE job 
                SET launched_by = '1' 
                WHERE launched_by IS NULL 
                OR launched_by = ''
            ");
            
            // Convert text values to user IDs where possible (match by username)
            $this->execute("
                UPDATE job 
                INNER JOIN user ON user.login = job.launched_by
                SET job.launched_by = CAST(user.id AS CHAR)
                WHERE job.launched_by NOT REGEXP '^[0-9]+$'
            ");
            
            // For remaining non-numeric values, set to admin
            $this->execute("
                UPDATE job 
                SET launched_by = '1' 
                WHERE job.launched_by NOT REGEXP '^[0-9]+$'
            ");
        } elseif ($databaseType === 'sqlite') {
            $this->execute("
                UPDATE job 
                SET launched_by = '1' 
                WHERE launched_by IS NULL 
                OR launched_by = ''
            ");
            
            // Try to match by username
            $this->execute("
                UPDATE job 
                SET launched_by = CAST((SELECT id FROM user WHERE login = job.launched_by) AS TEXT)
                WHERE NOT (launched_by GLOB '[0-9]*')
                AND EXISTS (SELECT 1 FROM user WHERE login = job.launched_by)
            ");
            
            // Set remaining to admin
            $this->execute("
                UPDATE job 
                SET launched_by = '1' 
                WHERE NOT (launched_by GLOB '[0-9]*')
            ");
        } elseif ($databaseType === 'pgsql') {
            $this->execute("
                UPDATE job 
                SET launched_by = '1' 
                WHERE launched_by IS NULL 
                OR launched_by = ''
            ");
            
            // Match by username
            $this->execute("
                UPDATE job 
                SET launched_by = u.id::TEXT
                FROM \"user\" u
                WHERE u.login = job.launched_by
                AND job.launched_by !~ '^[0-9]+$'
            ");
            
            // Set remaining to admin
            $this->execute("
                UPDATE job 
                SET launched_by = '1' 
                WHERE launched_by !~ '^[0-9]+$'
            ");
        }
        
        // Now change the column type to integer
        $table->changeColumn(
            'launched_by',
            'integer',
            array_merge([
                'null' => false,
                'comment' => 'User ID who scheduled this job (foreign key to user.id)'
            ], $unsigned)
        );
        
        // Add foreign key constraint
        $table->addForeignKey(
            'launched_by',
            'user',
            ['id'],
            [
                'constraint' => 'job_launched_by_user_fk',
                'delete' => 'RESTRICT',
                'update' => 'CASCADE'
            ]
        );
        
        // Add index for performance
        $table->addIndex(['launched_by'], ['name' => 'idx_job_launched_by']);
        
        $table->update();
    }
    
    /**
     * Revert the changes - convert back to text.
     */
    public function down(): void
    {
        $table = $this->table('job');
        
        // Remove foreign key
        $table->dropForeignKey('launched_by');
        
        // Remove index
        if ($this->hasIndex('job', ['launched_by'])) {
            $table->removeIndex(['launched_by']);
        }
        
        // Change back to text
        $table->changeColumn(
            'launched_by',
            'text',
            [
                'null' => true,
                'default' => null,
                'comment' => 'launched by'
            ]
        );
        
        $table->update();
    }
}
