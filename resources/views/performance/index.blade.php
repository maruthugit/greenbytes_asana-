@extends('layouts.app')

@section('content')
@php
	$control = 'h-10 rounded-md border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';
	$controlWide = $control . ' w-44';
	$btn = 'inline-flex h-10 items-center justify-center rounded-md border border-blue-600 bg-white px-4 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50';
	$btnMuted = 'inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50';
@endphp

<div class="mb-3">
	<h1 class="text-xl font-semibold text-slate-900">Performance</h1>
	<p class="mt-0.5 text-sm text-slate-500">High-level workload and due-soon view.</p>
</div>

<div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
	<div class="flex flex-wrap items-center justify-between gap-2">
		<div class="flex flex-wrap items-center gap-2">
			<select id="perfBulkAction" class="{{ $controlWide }}" aria-label="Bulk actions">
			<option value="">Bulk actions</option>
			<option value="export_csv">Export CSV</option>
			<option value="export_xlsx">Export XLSX</option>
			<option value="reset">Reset (All time)</option>
			</select>

<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
			<button type="button" id="perfBulkApply" class="{{ $btn }}">Apply</button>

			<form method="GET" action="{{ route('performance') }}" class="flex flex-wrap items-center gap-2">
			<div class="relative" data-datewrap>
				<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" data-overlay>Date From</span>
				<input
					type="date"
					name="from"
					value="{{ $fromDate ?? request('from') }}"
					class="{{ $controlWide }} relative bg-transparent"
					autocomplete="off"
				/>
			</div>
			<div class="relative" data-datewrap>
				<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" data-overlay>Date To</span>
				<input
					type="date"
					name="to"
					value="{{ $toDate ?? request('to') }}"
					class="{{ $controlWide }} relative bg-transparent"
					autocomplete="off"
				/>
			</div>
			<button class="{{ $btn }}">Filter</button>
			</form>
		</div>
		<a href="{{ route('performance') }}" class="{{ $btnMuted }}">Clear</a>
	</div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
	<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
		<div class="text-xs font-medium text-slate-500">Total tasks</div>
		<div class="mt-1 text-2xl font-semibold text-slate-900">{{ $totalTasks }}</div>
	</div>

	<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
		<div class="text-xs font-medium text-slate-500">Open tasks</div>
		<div class="mt-1 text-2xl font-semibold text-slate-900">
			{{ ($tasksByStatus['Todo'] ?? 0) + ($tasksByStatus['Doing'] ?? 0) }}
		</div>
		<div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-600">
			<span class="rounded-full bg-slate-100 px-2 py-0.5">Todo {{ $tasksByStatus['Todo'] ?? 0 }}</span>
			<span class="rounded-full bg-slate-100 px-2 py-0.5">Doing {{ $tasksByStatus['Doing'] ?? 0 }}</span>
		</div>
	</div>

	<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
		<div class="text-xs font-medium text-slate-500">Done</div>
		<div class="mt-1 text-2xl font-semibold text-slate-900">{{ $tasksByStatus['Done'] ?? 0 }}</div>
	</div>
</div>

@php
	$statusLabels = ['Todo', 'Doing', 'Done'];
	$statusValues = [
		(int) ($tasksByStatus['Todo'] ?? 0),
		(int) ($tasksByStatus['Doing'] ?? 0),
		(int) ($tasksByStatus['Done'] ?? 0),
	];
	$statusColors = ['#94A3B8', '#F59E0B', '#22C55E'];

	$assigneeLabels = [];
	$assigneeValues = [];
	foreach ($tasksByAssignee as $assigneeId => $count) {
		$name = $assigneeId ? ($assigneeNames[$assigneeId] ?? 'Unknown') : 'Unassigned';
		$assigneeLabels[] = $name;
		$assigneeValues[] = (int) $count;
	}

	$memberLabels = [];
	$memberRates = [];
	foreach (($memberPerformance ?? collect())->take(12) as $row) {
		$total = (int) ($row->total_assigned ?? 0);
		$rate = $total === 0 ? 0 : (int) ($row->completion_rate ?? 0);
		$memberLabels[] = (string) ($row->name ?? '');
		$memberRates[] = $rate;
	}
@endphp

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
	<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
		<div class="flex items-center justify-between">
			<div>
				<div class="text-sm font-semibold text-slate-900">Tasks by status</div>
				<div class="mt-0.5 text-xs text-slate-500">Todo / Doing / Done</div>
			</div>
		</div>
		<div class="mt-4" style="height: 260px;">
			<canvas id="gbPerfStatusChart" aria-label="Tasks by status chart" role="img"></canvas>
			<div id="gbPerfChartFallback" class="hidden mt-3 text-xs text-slate-500">
				Charts couldn’t load (offline / blocked CDN).
			</div>
		</div>
	</div>

	<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
		<div>
			<div class="text-sm font-semibold text-slate-900">Top assignees</div>
			<div class="mt-0.5 text-xs text-slate-500">Open + done tasks (Top 10)</div>
		</div>
		<div class="mt-4" style="height: 260px;">
			<canvas id="gbPerfAssigneeChart" aria-label="Tasks by assignee chart" role="img"></canvas>
		</div>
	</div>
</div>

<div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
	<div>
		<div class="text-sm font-semibold text-slate-900">Completion rate by member</div>
		<div class="mt-0.5 text-xs text-slate-500">Top 12 (based on current filters)</div>
	</div>
	<div class="mt-4" style="height: 320px;">
		<canvas id="gbPerfCompletionChart" aria-label="Completion rate by member chart" role="img"></canvas>
	</div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
	<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-5 py-3">
			<div class="text-sm font-semibold text-slate-900">Tasks by assignee</div>
			<div class="text-xs text-slate-500">Top 10</div>
		</div>
		<div class="divide-y divide-slate-100">
			@forelse($tasksByAssignee as $assigneeId => $count)
				@php
					$name = $assigneeId ? ($assigneeNames[$assigneeId] ?? 'Unknown') : 'Unassigned';
				@endphp
				<div class="flex items-center justify-between gap-3 px-5 py-3">
					<div class="min-w-0 truncate text-sm font-medium text-slate-800">{{ $name }}</div>
					<div class="text-sm font-semibold text-slate-900">{{ $count }}</div>
				</div>
			@empty
				<div class="px-5 py-8 text-sm text-slate-500">No tasks yet.</div>
			@endforelse
		</div>
	</div>

	<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-5 py-3">
			<div class="text-sm font-semibold text-slate-900">Due in next 7 days</div>
			<div class="text-xs text-slate-500">Excludes Done</div>
		</div>
		<div class="divide-y divide-slate-100">
			@forelse($dueSoonTasks as $task)
				<div class="px-5 py-4">
					<div class="flex items-start justify-between gap-3">
						<div class="min-w-0">
							<div class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</div>
							<div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
								<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->status }}</span>
								@if($task->project)
									<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->project->name }}</span>
								@endif
								@if($task->assignee)
									<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->assignee->name }}</span>
								@endif
								@if($task->due_date)
									<span class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-800">Due {{ $task->due_date->format('Y-m-d') }}</span>
								@endif
							</div>
						</div>
						<div class="text-xs text-slate-400">#{{ $task->id }}</div>
					</div>
				</div>
			@empty
				<div class="px-5 py-8 text-sm text-slate-500">Nothing due soon.</div>
			@endforelse
		</div>
	</div>
</div>

<div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
	<div class="border-b border-slate-200 px-5 py-3">
		<div class="text-sm font-semibold text-slate-900">Member performance</div>
		<div class="text-xs text-slate-500">Assigned task workload and completion</div>
	</div>

	<div class="overflow-x-auto">
		<table class="min-w-full divide-y divide-slate-100">
			<thead class="bg-slate-50">
				<tr>
					<th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Member</th>
					<th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Open</th>
					<th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Done</th>
					<th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Overdue</th>
					<th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Due 7d</th>
					<th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
					<th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Completion</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-100">
				@forelse(($memberPerformance ?? collect()) as $row)
					@php
						$open = (int) ($row->open_count ?? 0);
						$done = (int) ($row->done_count ?? 0);
						$overdue = (int) ($row->overdue_count ?? 0);
						$dueSoon = (int) ($row->due_soon_count ?? 0);
						$total = (int) ($row->total_assigned ?? 0);
						$rate = $total === 0 ? 0 : (int) ($row->completion_rate ?? 0);
					@endphp
					<tr class="hover:bg-slate-50">
						<td class="px-5 py-4 text-sm font-medium text-slate-900">
							<div class="truncate">{{ $row->name }}</div>
							<div class="mt-2 h-1.5 w-40 rounded-full bg-slate-100">
								<div class="h-1.5 rounded-full bg-indigo-600" style="width: {{ $rate }}%"></div>
							</div>
						</td>
						<td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">{{ $open }}</td>
						<td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">{{ $done }}</td>
						<td class="px-5 py-4 text-right text-sm font-semibold {{ $overdue ? 'text-rose-700' : 'text-slate-900' }}">{{ $overdue }}</td>
						<td class="px-5 py-4 text-right text-sm font-semibold {{ $dueSoon ? 'text-amber-800' : 'text-slate-900' }}">{{ $dueSoon }}</td>
						<td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">{{ $total }}</td>
						<td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">
							@if($total === 0)
								<span class="text-slate-400">—</span>
							@else
								{{ $rate }}%
							@endif
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="7" class="px-5 py-8 text-sm text-slate-500">No members found.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
<script>
	(() => {
		const applyBtn = document.getElementById('perfBulkApply');
		const select = document.getElementById('perfBulkAction');
		if (!applyBtn || !select) return;

		applyBtn.addEventListener('click', () => {
			const action = select.value;
			if (!action) return;

			if (action === 'reset') {
				window.location.href = @json(route('performance'));
				return;
			}

			const fromInput = document.querySelector('input[name="from"]');
			const toInput = document.querySelector('input[name="to"]');
			const params = new URLSearchParams();
			if (fromInput && fromInput.value) params.set('from', fromInput.value);
			if (toInput && toInput.value) params.set('to', toInput.value);

			let url = '';
			if (action === 'export_csv') url = @json(route('performance.export'));
			if (action === 'export_xlsx') url = @json(route('performance.export.xlsx'));
			if (!url) return;

			const query = params.toString();
			window.location.href = url + (query ? ('?' + query) : '');
			select.value = '';
		});
	})();
</script>

<script>
	(() => {
		const statusLabels = @json($statusLabels);
		const statusValues = @json($statusValues);
		const statusColors = @json($statusColors);
		const assigneeLabels = @json($assigneeLabels);
		const assigneeValues = @json($assigneeValues);
		const memberLabels = @json($memberLabels);
		const memberRates = @json($memberRates);

		function showFallback() {
			const el = document.getElementById('gbPerfChartFallback');
			if (el) el.classList.remove('hidden');
		}

		if (!window.Chart) {
			showFallback();
			return;
		}

		const commonOptions = {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { labels: { boxWidth: 12 } },
				tooltip: { enabled: true },
			},
		};

		const statusEl = document.getElementById('gbPerfStatusChart');
		if (statusEl) {
			new Chart(statusEl, {
				type: 'doughnut',
				data: {
					labels: statusLabels,
					datasets: [{
						data: statusValues,
						backgroundColor: statusColors,
						borderWidth: 0,
					}],
				},
				options: {
					...commonOptions,
					cutout: '68%',
				},
			});
		}

		const assigneeEl = document.getElementById('gbPerfAssigneeChart');
		if (assigneeEl) {
			new Chart(assigneeEl, {
				type: 'bar',
				data: {
					labels: assigneeLabels,
					datasets: [{
						label: 'Tasks',
						data: assigneeValues,
						backgroundColor: '#6366F1',
						borderRadius: 8,
						borderSkipped: false,
					}],
				},
				options: {
					...commonOptions,
					scales: {
						x: { ticks: { maxRotation: 0, autoSkip: true } },
						y: { beginAtZero: true, precision: 0 },
					},
				},
			});
		}

		const completionEl = document.getElementById('gbPerfCompletionChart');
		if (completionEl) {
			new Chart(completionEl, {
				type: 'bar',
				data: {
					labels: memberLabels,
					datasets: [{
						label: 'Completion %',
						data: memberRates,
						backgroundColor: '#0EA5E9',
						borderRadius: 8,
						borderSkipped: false,
					}],
				},
				options: {
					...commonOptions,
					indexAxis: 'y',
					scales: {
						x: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } },
						y: { ticks: { autoSkip: false } },
					},
				},
			});
		}
	})();
</script>
<script>
	(() => {
		function syncOverlay(input) {
			const wrap = input.closest('[data-datewrap]');
			if (!wrap) return;
			const overlay = wrap.querySelector('[data-overlay]');
			if (!overlay) return;
			overlay.style.display = input.value ? 'none' : 'block';
		}

		document.querySelectorAll('[data-datewrap] input').forEach((input) => {
			syncOverlay(input);
			input.addEventListener('input', () => syncOverlay(input));
			input.addEventListener('change', () => syncOverlay(input));
			input.addEventListener('focus', () => {
				const wrap = input.closest('[data-datewrap]');
				const overlay = wrap ? wrap.querySelector('[data-overlay]') : null;
				if (overlay) overlay.style.display = 'none';
			});
			input.addEventListener('blur', () => syncOverlay(input));
		});
	})();
</script>
@endsection
