<?php

use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tasks:backfill-assignee-activity-names {--dry-run}', function () {
    $dryRun = (bool) $this->option('dry-run');

    $query = TaskActivity::query()->where('type', 'assignee.changed');

    $total = (clone $query)->count();
    $this->info('Scanning ' . $total . ' assignee.changed activities...');

    $updated = 0;
    $skipped = 0;

    $query->orderBy('id')->chunkById(200, function ($rows) use (&$updated, &$skipped, $dryRun) {
        $idsToResolve = [];

        foreach ($rows as $a) {
            $meta = (array) ($a->meta ?? []);
            $fromId = $meta['from'] ?? null;
            $toId = $meta['to'] ?? null;
            $fromName = trim((string) ($meta['from_name'] ?? ''));
            $toName = trim((string) ($meta['to_name'] ?? ''));

            if ($fromName === '' && $fromId) {
                $idsToResolve[] = (int) $fromId;
            }
            if ($toName === '' && $toId) {
                $idsToResolve[] = (int) $toId;
            }
        }

        $idsToResolve = collect($idsToResolve)->unique()->values();
        $names = $idsToResolve->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $idsToResolve)->pluck('name', 'id');

        foreach ($rows as $a) {
            $meta = (array) ($a->meta ?? []);
            $fromId = $meta['from'] ?? null;
            $toId = $meta['to'] ?? null;

            $beforeFromName = trim((string) ($meta['from_name'] ?? ''));
            $beforeToName = trim((string) ($meta['to_name'] ?? ''));

            $newFromName = $beforeFromName;
            $newToName = $beforeToName;

            if ($newFromName === '' && $fromId) {
                $newFromName = (string) ($names[(int) $fromId] ?? '');
            }
            if ($newToName === '' && $toId) {
                $newToName = (string) ($names[(int) $toId] ?? '');
            }

            $changed = false;
            if ($beforeFromName !== $newFromName) {
                $meta['from_name'] = $newFromName !== '' ? $newFromName : null;
                $changed = true;
            }
            if ($beforeToName !== $newToName) {
                $meta['to_name'] = $newToName !== '' ? $newToName : null;
                $changed = true;
            }

            if (!$changed) {
                $skipped++;
                continue;
            }

            $updated++;

            if ($dryRun) {
                continue;
            }

            $a->meta = $meta;
            $a->save();
        }
    });

    if ($dryRun) {
        $this->warn('Dry run: no rows were saved.');
    }

    $this->info('Updated: ' . $updated . '  Skipped: ' . $skipped);
})->purpose('Backfill from_name/to_name for assignee.changed task activities');
