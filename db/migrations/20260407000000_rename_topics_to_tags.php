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

final class RenameTopicsToTags extends AbstractMigration
{
    public function up(): void
    {
        $this->table('apps')
            ->renameColumn('topics', 'tags')
            ->update();
    }

    public function down(): void
    {
        $this->table('apps')
            ->renameColumn('tags', 'topics')
            ->update();
    }
}
