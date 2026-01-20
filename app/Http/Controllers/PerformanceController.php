<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class PerformanceController extends Controller
{
    private function accessibleTeamIds(): Collection
    {
        $user = auth()->user();

		if ($user && $user->hasRole('admin')) {
			return Team::query()->pluck('id');
		}

        $owned = $user->ownedTeams()->pluck('teams.id');
        $memberOf = $user->teams()->pluck('teams.id');

        return $owned->merge($memberOf)->unique()->values();
    }

    private function accessibleUserIds(Collection $teamIds): Collection
    {
        $teamIds = $teamIds->values();

        $ownerIds = DB::table('teams')
            ->whereIn('id', $teamIds)
            ->pluck('user_id');

        $memberIds = DB::table('team_user')
            ->whereIn('team_id', $teamIds)
            ->pluck('user_id');

        return $ownerIds->merge($memberIds)->filter()->unique()->values();
    }

    private function dateRange(Request $request): array
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $range = $request->query('range');

        $fromDate = null;
        $toDate = null;

        if (is_string($from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $fromDate = $from;
        }

        if (is_string($to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $toDate = $to;
        }

        if (!$fromDate && !$toDate && is_string($range) && $range !== '') {
            $now = now();

            if ($range === 'today') {
                $fromDate = $now->toDateString();
                $toDate = $now->toDateString();
            } elseif ($range === 'last7') {
                $fromDate = $now->copy()->subDays(6)->toDateString();
                $toDate = $now->toDateString();
            } elseif ($range === 'this_month') {
                $fromDate = $now->copy()->startOfMonth()->toDateString();
                $toDate = $now->copy()->endOfMonth()->toDateString();
            } elseif ($range === 'last_month') {
                $fromDate = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $toDate = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
            } elseif ($range === 'this_year') {
                $fromDate = $now->copy()->startOfYear()->toDateString();
                $toDate = $now->copy()->endOfYear()->toDateString();
            }
        }

        return [$fromDate, $toDate];
    }

    private function applyCreatedAtDateFilter($query, ?string $fromDate, ?string $toDate)
    {
        if ($fromDate) {
            $query->whereDate('tasks.created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tasks.created_at', '<=', $toDate);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $teamIds = $this->accessibleTeamIds();
        $userIds = $this->accessibleUserIds($teamIds);

        [$fromDate, $toDate] = $this->dateRange($request);

        $projectIds = Project::query()
            ->whereIn('team_id', $teamIds)
            ->pluck('id');

        $tasksQuery = Task::query()->whereIn('project_id', $projectIds);
        $this->applyCreatedAtDateFilter($tasksQuery, $fromDate, $toDate);

        $totalTasks = (clone $tasksQuery)->count();

        $tasksByStatus = (clone $tasksQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $tasksByAssignee = (clone $tasksQuery)
            ->select('assigned_to', DB::raw('count(*) as count'))
            ->groupBy('assigned_to')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'assigned_to');

        $assigneeNames = User::query()
            ->whereIn('id', $tasksByAssignee->keys()->filter())
            ->pluck('name', 'id');

        $dueSoonTasks = (clone $tasksQuery)
            ->whereNotNull('due_date')
            ->where('status', '!=', 'Done')
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->with(['project', 'assignee'])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $today = now()->toDateString();
        $next7 = now()->addDays(7)->toDateString();

        $memberPerformance = User::query()
            ->whereIn('users.id', $userIds)
            ->leftJoin('tasks', function ($join) use ($projectIds) {
                $join->on('tasks.assigned_to', '=', 'users.id')
                    ->whereIn('tasks.project_id', $projectIds);
            })
            ->when($fromDate, fn ($q) => $q->whereDate('tasks.created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('tasks.created_at', '<=', $toDate))
            ->select(['users.id', 'users.name'])
            ->selectRaw('COUNT(tasks.id) as total_assigned')
            ->selectRaw("SUM(CASE WHEN tasks.status IN ('Todo','Doing') THEN 1 ELSE 0 END) as open_count")
            ->selectRaw("SUM(CASE WHEN tasks.status = 'Done' THEN 1 ELSE 0 END) as done_count")
            ->selectRaw(
                "SUM(CASE WHEN tasks.status != 'Done' AND tasks.due_date IS NOT NULL AND tasks.due_date < ? THEN 1 ELSE 0 END) as overdue_count",
                [$today]
            )
            ->selectRaw(
                "SUM(CASE WHEN tasks.status != 'Done' AND tasks.due_date IS NOT NULL AND tasks.due_date >= ? AND tasks.due_date <= ? THEN 1 ELSE 0 END) as due_soon_count",
                [$today, $next7]
            )
            ->selectRaw(
                "ROUND((1.0 * SUM(CASE WHEN tasks.status = 'Done' THEN 1 ELSE 0 END) / NULLIF(COUNT(tasks.id), 0)) * 100, 0) as completion_rate"
            )
            ->groupBy(['users.id', 'users.name'])
            ->orderByDesc('open_count')
            ->orderByDesc('done_count')
            ->orderBy('users.name')
            ->get();

        return view('performance.index', [
            'totalTasks' => $totalTasks,
            'tasksByStatus' => $tasksByStatus,
            'tasksByAssignee' => $tasksByAssignee,
            'assigneeNames' => $assigneeNames,
            'dueSoonTasks' => $dueSoonTasks,
            'memberPerformance' => $memberPerformance,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    private function buildExportData(Request $request): array
    {
        $teamIds = $this->accessibleTeamIds();
        $userIds = $this->accessibleUserIds($teamIds);

        [$fromDate, $toDate] = $this->dateRange($request);

        $projectIds = Project::query()
            ->whereIn('team_id', $teamIds)
            ->pluck('id');

        $tasksQuery = Task::query()->whereIn('project_id', $projectIds);
        $this->applyCreatedAtDateFilter($tasksQuery, $fromDate, $toDate);

        $totalTasks = (clone $tasksQuery)->count();
        $tasksByStatus = (clone $tasksQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $today = now()->toDateString();
        $next7 = now()->addDays(7)->toDateString();

        $dueSoonTasks = (clone $tasksQuery)
            ->whereNotNull('due_date')
            ->where('status', '!=', 'Done')
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->with(['project', 'assignee'])
            ->orderBy('due_date')
            ->limit(200)
            ->get();

        $memberPerformance = User::query()
            ->whereIn('users.id', $userIds)
            ->leftJoin('tasks', function ($join) use ($projectIds) {
                $join->on('tasks.assigned_to', '=', 'users.id')
                    ->whereIn('tasks.project_id', $projectIds);
            })
            ->when($fromDate, fn ($q) => $q->whereDate('tasks.created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('tasks.created_at', '<=', $toDate))
            ->select(['users.id', 'users.name'])
            ->selectRaw('COUNT(tasks.id) as total_assigned')
            ->selectRaw("SUM(CASE WHEN tasks.status IN ('Todo','Doing') THEN 1 ELSE 0 END) as open_count")
            ->selectRaw("SUM(CASE WHEN tasks.status = 'Done' THEN 1 ELSE 0 END) as done_count")
            ->selectRaw(
                "SUM(CASE WHEN tasks.status != 'Done' AND tasks.due_date IS NOT NULL AND tasks.due_date < ? THEN 1 ELSE 0 END) as overdue_count",
                [$today]
            )
            ->selectRaw(
                "SUM(CASE WHEN tasks.status != 'Done' AND tasks.due_date IS NOT NULL AND tasks.due_date >= ? AND tasks.due_date <= ? THEN 1 ELSE 0 END) as due_soon_count",
                [$today, $next7]
            )
            ->selectRaw(
                "ROUND((1.0 * SUM(CASE WHEN tasks.status = 'Done' THEN 1 ELSE 0 END) / NULLIF(COUNT(tasks.id), 0)) * 100, 0) as completion_rate"
            )
            ->groupBy(['users.id', 'users.name'])
            ->orderBy('users.name')
            ->get();

        return [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'totalTasks' => $totalTasks,
            'tasksByStatus' => $tasksByStatus,
            'memberPerformance' => $memberPerformance,
            'dueSoonTasks' => $dueSoonTasks,
        ];
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->buildExportData($request);
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $totalTasks = $data['totalTasks'];
        $tasksByStatus = $data['tasksByStatus'];
        $rows = $data['memberPerformance'];
        $dueSoonTasks = $data['dueSoonTasks'];

        $nameSuffix = trim(implode('_', array_filter([$fromDate, $toDate])));
        $fileName = 'member-performance' . ($nameSuffix ? ('_' . $nameSuffix) : '') . '.csv';

        return response()->streamDownload(function () use ($rows, $fromDate, $toDate, $totalTasks, $tasksByStatus, $dueSoonTasks) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Member performance export']);
            fputcsv($out, ['Created date filter', $fromDate ?: 'All', $toDate ?: 'All']);
            fputcsv($out, []);

            fputcsv($out, ['Summary']);
            fputcsv($out, ['total_tasks', (int) $totalTasks]);
            fputcsv($out, ['todo', (int) ($tasksByStatus['Todo'] ?? 0)]);
            fputcsv($out, ['doing', (int) ($tasksByStatus['Doing'] ?? 0)]);
            fputcsv($out, ['done', (int) ($tasksByStatus['Done'] ?? 0)]);
            fputcsv($out, []);

            fputcsv($out, ['Member performance']);
            fputcsv($out, ['user_id', 'name', 'open', 'done', 'overdue', 'due_7d', 'total', 'completion_rate_percent']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->name,
                    (int) ($r->open_count ?? 0),
                    (int) ($r->done_count ?? 0),
                    (int) ($r->overdue_count ?? 0),
                    (int) ($r->due_soon_count ?? 0),
                    (int) ($r->total_assigned ?? 0),
                    is_null($r->completion_rate) ? 0 : (int) $r->completion_rate,
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Due soon (next 7 days, excludes Done)']);
            fputcsv($out, ['task_id', 'title', 'status', 'project', 'assignee', 'due_date']);

            foreach ($dueSoonTasks as $task) {
                fputcsv($out, [
                    $task->id,
                    $task->title,
                    $task->status,
                    optional($task->project)->name,
                    optional($task->assignee)->name,
                    optional($task->due_date)->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportXlsx(Request $request): StreamedResponse
    {
        $data = $this->buildExportData($request);

        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $totalTasks = $data['totalTasks'];
        $tasksByStatus = $data['tasksByStatus'];
        $rows = $data['memberPerformance'];
        $dueSoonTasks = $data['dueSoonTasks'];

        $nameSuffix = trim(implode('_', array_filter([$fromDate, $toDate])));
        $fileName = 'member-performance' . ($nameSuffix ? ('_' . $nameSuffix) : '') . '.xlsx';

        return response()->streamDownload(function () use ($rows, $fromDate, $toDate, $totalTasks, $tasksByStatus, $dueSoonTasks) {
            $writer = new XlsxWriter();
            $writer->openToFile('php://output');

            // Sheet 1: Summary
            $writer->getCurrentSheet()->setName('Summary');
            $writer->addRow(Row::fromValues(['Member performance export']));
            $writer->addRow(Row::fromValues(['Created date filter', $fromDate ?: 'All', $toDate ?: 'All']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['total_tasks', (int) $totalTasks]));
            $writer->addRow(Row::fromValues(['todo', (int) ($tasksByStatus['Todo'] ?? 0)]));
            $writer->addRow(Row::fromValues(['doing', (int) ($tasksByStatus['Doing'] ?? 0)]));
            $writer->addRow(Row::fromValues(['done', (int) ($tasksByStatus['Done'] ?? 0)]));

            // Sheet 2: Member performance
            $writer->addNewSheetAndMakeItCurrent()->setName('Members');
            $writer->addRow(Row::fromValues(['user_id', 'name', 'open', 'done', 'overdue', 'due_7d', 'total', 'completion_rate_percent']));
            foreach ($rows as $r) {
                $writer->addRow(Row::fromValues([
                    $r->id,
                    $r->name,
                    (int) ($r->open_count ?? 0),
                    (int) ($r->done_count ?? 0),
                    (int) ($r->overdue_count ?? 0),
                    (int) ($r->due_soon_count ?? 0),
                    (int) ($r->total_assigned ?? 0),
                    is_null($r->completion_rate) ? 0 : (int) $r->completion_rate,
                ]));
            }

            // Sheet 3: Due soon
            $writer->addNewSheetAndMakeItCurrent()->setName('Due soon');
            $writer->addRow(Row::fromValues(['task_id', 'title', 'status', 'project', 'assignee', 'due_date']));
            foreach ($dueSoonTasks as $task) {
                $writer->addRow(Row::fromValues([
                    $task->id,
                    $task->title,
                    $task->status,
                    optional($task->project)->name,
                    optional($task->assignee)->name,
                    optional($task->due_date)->format('Y-m-d'),
                ]));
            }

            $writer->close();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
