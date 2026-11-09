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
 * Invoice item dispatch (see invoice_item_dispatch.md).
 *
 * event_rule: an optional per-item predicate and the fan-out mode, so a paid
 *             invoice can start one RunTemplate per matching item.
 * task:       an event-driven work item is a Task. It needs an idempotency key
 *             (a replayed hook must not create a second Task), the event
 *             context (env overrides repeated on every attempt), the last error
 *             and the time of the next attempt.
 */
final class EventItemDispatch extends AbstractMigration
{
    public function change(): void
    {
        $rule = $this->table('event_rule');

        if (!$this->hasTable('event_rule')) {
            return;
        }

        if (!$rule->hasColumn('item_filter')) {
            $rule->addColumn('item_filter', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON predicate on a single document item, e.g. {"cenik":"code:MULTIFLEXI"}; null = whole-document rule',
            ]);
        }

        if (!$rule->hasColumn('fanout')) {
            $rule->addColumn('fanout', 'string', [
                'length' => 8,
                'null' => false,
                'default' => 'none',
                'comment' => 'none | item - item: one work item per document item matching item_filter',
            ]);
        }

        $rule->save();

        if (!$this->hasTable('task')) {
            return;
        }

        $task = $this->table('task');

        if (!$task->hasColumn('origin')) {
            $task->addColumn('origin', 'string', [
                'length' => 16,
                'null' => false,
                'default' => 'schedule',
                'comment' => 'schedule (periodic window) | event (dispatched by an event rule)',
            ]);
        }

        if (!$task->hasColumn('idempotency_key')) {
            $task->addColumn('idempotency_key', 'string', [
                'length' => 64,
                'null' => true,
                'default' => null,
                'comment' => 'sha256 of source|evidence|record|metastate|item|rule; a repeated delivery inserts nothing',
            ]);
        }

        if (!$task->hasColumn('context')) {
            $task->addColumn('context', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON env overrides applied to every attempt of an event task',
            ]);
        }

        if (!$task->hasColumn('last_error')) {
            $task->addColumn('last_error', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Last failure reason shown to the operator',
            ]);
        }

        if (!$task->hasColumn('next_attempt_at')) {
            $task->addColumn('next_attempt_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the next attempt of an open event task is due',
            ]);
        }

        $task->save();

        $task = $this->table('task');

        if (!$task->hasIndex(['idempotency_key'])) {
            $task->addIndex(['idempotency_key'], ['unique' => true, 'name' => 'task_idempotency_key_unique']);
        }

        if (!$task->hasIndex(['origin', 'state', 'next_attempt_at'])) {
            $task->addIndex(['origin', 'state', 'next_attempt_at'], ['name' => 'task_event_due']);
        }

        $task->update();
    }
}
