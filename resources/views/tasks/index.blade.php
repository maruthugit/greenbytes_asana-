@extends('layouts.app')
@section('content')
@php
	$view = $view ?? 'list';
	$showDetail = in_array(($selectedMode ?? 'none'), ['new', 'view'], true);
@endphp
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
	<div>
		<h1 class="text-xl font-semibold text-slate-900">My tasks</h1>
		<p class="text-sm text-slate-500">Select a task to view details and add comments.</p>
	</div>
	@canany(['tasks.manage', 'tasks.create'])
		<a href="{{ url('/tasks') . '?' . http_build_query(['view' => $view, 'task' => 'new']) }}" class="rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Add task</a>
	@endcanany
</div>

<div class="mb-4 rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
	<div class="flex flex-wrap items-center gap-2">
		@php
			$tabTask = (string) request('task', '');
			$tabTask = trim($tabTask);
			$tabQuery = function (string $viewName) use ($tabTask): string {
				$params = ['view' => $viewName];
				if ($tabTask !== '') {
					$params['task'] = $tabTask;
				}
				return url('/tasks') . '?' . http_build_query($params);
			};
		@endphp
		<a data-no-task-spa href="{{ $tabQuery('list') }}" class="rounded-xl px-3 py-2 text-sm font-semibold {{ $view === 'list' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}">List</a>
		<a data-no-task-spa href="{{ $tabQuery('board') }}" class="rounded-xl px-3 py-2 text-sm font-semibold {{ $view === 'board' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}">Board</a>
		<a data-no-task-spa href="{{ $tabQuery('calendar') }}" class="rounded-xl px-3 py-2 text-sm font-semibold {{ $view === 'calendar' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}">Calendar</a>
		<a data-no-task-spa href="{{ $tabQuery('files') }}" class="rounded-xl px-3 py-2 text-sm font-semibold {{ $view === 'files' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}">Files</a>
	</div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
	<div id="tasks-master" class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-12">
		<div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
			<div class="text-sm font-semibold text-slate-900">
				@if($view === 'board') Board
				@elseif($view === 'calendar') Calendar
				@elseif($view === 'files') Files
				@else Task list
				@endif
			</div>
			<div class="text-xs text-slate-500">{{ count($tasks) }} total</div>
		</div>

		@if($view === 'board')
			@php
				$byStatus = $tasksByStatus ?? collect();
			@endphp
			<div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-3">
				@foreach(['Todo','Doing','Done'] as $status)
					<div class="rounded-2xl border border-slate-200 bg-slate-50">
						<div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
							<div class="text-sm font-semibold text-slate-900">{{ $status }}</div>
							<div class="text-xs text-slate-500">{{ ($byStatus[$status] ?? collect())->count() }}</div>
						</div>
						<div class="space-y-2 p-3">
							@foreach(($byStatus[$status] ?? collect()) as $task)
								<a data-task-id="{{ $task->id }}" href="{{ url('/tasks') . '?' . http_build_query(['view' => 'board', 'task' => $task->id]) }}" class="block rounded-xl border border-slate-200 bg-white p-3 hover:border-slate-300">
									<div class="text-sm font-semibold text-slate-900">{{ $task->title }}</div>
									<div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
										@if($task->project)
											<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->project->name }}</span>
										@endif
										@if($task->assignee)
											<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->assignee->name }}</span>
										@endif
										@if($task->due_date)
											<span class="rounded-full bg-slate-100 px-2 py-0.5">Due {{ $task->due_date->format('Y-m-d') }}</span>
										@endif
									</div>
								</a>
							@endforeach
							@if(($byStatus[$status] ?? collect())->count() === 0)
								<div class="px-2 py-3 text-sm text-slate-500">No tasks</div>
							@endif
						</div>
					</div>
				@endforeach
			</div>
		@elseif($view === 'calendar')
			@php
				$groups = $calendarGroups ?? collect();
			@endphp
			<div class="divide-y divide-slate-100">
				@forelse($groups as $date => $items)
					<div class="px-5 py-4">
						<div class="text-sm font-semibold text-slate-900">{{ $date }}</div>
						<div class="mt-2 space-y-2">
							@foreach($items as $task)
								<a data-task-id="{{ $task->id }}" href="{{ url('/tasks') . '?' . http_build_query(['view' => 'calendar', 'task' => $task->id]) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 hover:border-slate-300">
									<div class="min-w-0">
										<div class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</div>
										<div class="mt-0.5 text-xs text-slate-500">{{ $task->status }} @if($task->project) · {{ $task->project->name }} @endif</div>
									</div>
									<div class="shrink-0 text-xs text-slate-400">#{{ $task->id }}</div>
								</a>
							@endforeach
						</div>
					</div>
				@empty
					<div class="px-5 py-10 text-sm text-slate-500">No due dates yet.</div>
				@endforelse
			</div>
		@elseif($view === 'files')
			@php
				$items = $fileItems ?? collect();
			@endphp
			<div class="p-5">
				@php
					$filesParamsBase = array_filter([
						'view' => 'files',
						'files_q' => $files_q ?? request('files_q'),
						'files_type' => $files_type ?? request('files_type'),
						'files_project' => $files_project ?? request('files_project'),
						'files_sort' => $files_sort ?? request('files_sort'),
					], fn ($v) => $v !== null && $v !== '');
				@endphp

				<div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
					<form method="GET" action="/tasks" class="grid grid-cols-1 gap-3 md:grid-cols-12">
						<input type="hidden" name="view" value="files" />
						@if(request('task'))
							<input type="hidden" name="task" value="{{ request('task') }}" />
						@endif

						<div class="md:col-span-5">
							<label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
							<input name="files_q" value="{{ $files_q ?? request('files_q') }}" placeholder="Search files, tasks, projects" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" />
						</div>

						<div class="md:col-span-2">
							<label class="mb-1 block text-xs font-medium text-slate-600">Type</label>
							<select name="files_type" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
								@php
									$t = $files_type ?? request('files_type', 'all');
								@endphp
								<option value="all" @selected($t === 'all' || $t === '')>All</option>
								<option value="images" @selected($t === 'images')>Images</option>
								<option value="files" @selected($t === 'files')>Files</option>
							</select>
						</div>

						<div class="md:col-span-3">
							<label class="mb-1 block text-xs font-medium text-slate-600">Project</label>
							<select name="files_project" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
								@php
									$p = (string) ($files_project ?? request('files_project', ''));
								@endphp
								<option value="" @selected($p === '')>All projects</option>
								@foreach($projects as $project)
									<option value="{{ $project->id }}" @selected($p === (string) $project->id)>{{ $project->name }}</option>
								@endforeach
							</select>
						</div>

						<div class="md:col-span-2">
							<label class="mb-1 block text-xs font-medium text-slate-600">Sort</label>
							<select name="files_sort" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
								@php
									$s = $files_sort ?? request('files_sort', 'recent');
								@endphp
								<option value="recent" @selected($s === 'recent' || $s === '')>Recently added</option>
								<option value="task" @selected($s === 'task')>By task</option>
							</select>
						</div>

						<div class="md:col-span-12 flex items-center justify-end gap-2">
							<a href="{{ url('/tasks') . '?' . http_build_query(['view' => 'files']) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
							<button class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
						</div>
					</form>
				</div>

				@php
					$images = $items->filter(fn ($i) => ($i['kind'] ?? '') === 'image');
					$files = $items->filter(fn ($i) => ($i['kind'] ?? '') === 'file');
				@endphp

				@if($items->count() === 0)
					<div class="text-sm text-slate-500">No attachments yet.</div>
				@else
					@if($images->count())
						<div class="mb-4 text-sm font-semibold text-slate-900">Images</div>
						<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
							@foreach($images as $item)
								@php
									$task = $item['task'];
								@endphp
								<div class="group rounded-2xl border border-slate-200 bg-white p-3 hover:border-slate-300">
									<a href="{{ $item['url'] }}" target="_blank" class="block">
										<img src="{{ $item['url'] }}" alt="Attachment" class="h-28 w-full rounded-xl border border-slate-200 object-cover" loading="lazy" />
									</a>
									@php
										$taskLink = url('/tasks') . '?' . http_build_query(array_merge($filesParamsBase, ['task' => $task->id]));
									@endphp
									<a href="{{ $taskLink }}" class="mt-2 block truncate text-sm font-semibold text-slate-900 hover:underline">{{ $task->title }}</a>
									<div class="mt-0.5 truncate text-xs text-slate-500">
										@if($task->project){{ $task->project->name }}@endif
										@if(!empty($item['name'])) · {{ $item['name'] }} @endif
									</div>
									<div class="mt-3 flex flex-wrap items-center gap-2">
										<a href="{{ $item['url'] }}" target="_blank" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Open</a>
										<a href="{{ $item['url'] }}" download class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download</a>
										@canany(['tasks.manage', 'tasks.update', 'tasks.attachments.delete'])
											<form method="POST" action="{{ route('tasks.attachments.destroy', $task) }}" onsubmit="return confirm('Delete this attachment?');">
												@csrf
												@method('DELETE')
												<input type="hidden" name="url" value="{{ $item['url'] }}" />
												<button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
											</form>
										@endcanany
									</div>
								</div>
							@endforeach
						</div>
					@endif

					@if($files->count())
						<div class="mt-6 mb-3 text-sm font-semibold text-slate-900">Files</div>
						<div class="space-y-2">
							@foreach($files as $item)
								@php
									$task = $item['task'];
								@endphp
								<div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
									<div class="min-w-0">
										<div class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] ?? 'Attachment' }}</div>
										<div class="mt-0.5 truncate text-xs text-slate-500">
											From: {{ $task->title }} @if($task->project) · {{ $task->project->name }} @endif
											@if(!empty($item['ext'])) · {{ strtoupper($item['ext']) }} @endif
											@if(!empty($item['size_human'])) · {{ $item['size_human'] }} @endif
										</div>
									</div>
									<div class="flex shrink-0 items-center gap-2">
										<a href="{{ $item['url'] }}" target="_blank" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Open</a>
										<a href="{{ $item['url'] }}" download class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download</a>
										@canany(['tasks.manage', 'tasks.update', 'tasks.attachments.delete'])
											<form method="POST" action="{{ route('tasks.attachments.destroy', $task) }}" onsubmit="return confirm('Delete this attachment?');">
												@csrf
												@method('DELETE')
												<input type="hidden" name="url" value="{{ $item['url'] }}" />
												<button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
											</form>
										@endcanany
										@php
											$taskLink = url('/tasks') . '?' . http_build_query(array_merge($filesParamsBase, ['task' => $task->id]));
										@endphp
										<a href="{{ $taskLink }}" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Go to task</a>
									</div>
								</div>
							@endforeach
						</div>
					@endif
				@endif
			</div>
		@else
			@canany(['tasks.manage', 'tasks.create'])
				<div class="border-b border-slate-200 px-5 py-3">
					<a href="{{ url('/tasks') . '?' . http_build_query(['view' => 'list', 'task' => 'new']) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
						<span class="text-slate-500">+</span>
						Add task
					</a>
				</div>
			@endcanany
			@php
				$redirectParams = request()->query();
				unset($redirectParams['task'], $redirectParams['activity']);
				$redirectAfterDelete = url('/tasks') . (count($redirectParams) ? ('?' . http_build_query($redirectParams)) : '');
			@endphp
			<div class="divide-y divide-slate-100">
				@forelse($tasks as $task)
					@php
						$active = isset($selectedTask) && $selectedTask && $selectedTask->id === $task->id;
					@endphp
					<div data-task-id="{{ $task->id }}" class="px-5 py-4 transition {{ $active ? 'bg-indigo-50' : 'hover:bg-slate-50' }}">
						<div class="flex items-start justify-between gap-3">
							<a href="{{ url('/tasks') . '?' . http_build_query(['view' => 'list', 'task' => $task->id]) }}" class="min-w-0 flex-1">
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
										<span class="rounded-full bg-slate-100 px-2 py-0.5">Due {{ $task->due_date->format('Y-m-d') }}</span>
									@endif
									@if($task->comments_count ?? 0)
										<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->comments_count }}</span>
									@endif
								</div>
							</a>
							<div class="flex shrink-0 items-center gap-2">
								<div class="text-xs text-slate-400">#{{ $task->id }}</div>
								@canany(['tasks.manage', 'tasks.delete'])
									@role('admin')
										<form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task? This cannot be undone.');">
											@csrf
											@method('DELETE')
											<input type="hidden" name="redirect" value="{{ $redirectAfterDelete }}" />
											<button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
										</form>
									@endrole
								@endcanany
							</div>
						</div>
					</div>
				@empty
					<div class="px-5 py-8 text-sm text-slate-500">No tasks yet.</div>
				@endforelse
			</div>
		@endif
	</div>

	<div id="task-detail-backdrop" class="fixed inset-0 z-40 bg-black/30 lg:hidden {{ $showDetail ? '' : 'hidden' }}"></div>
	<style>
		#task-detail-root { width: 100%; max-width: 28rem; }
		@media (min-width: 1024px) {
			#task-detail-root { width: min(44vw, 48rem); max-width: none; }
		}
	</style>
	<div
		id="task-detail-root"
		class="fixed inset-y-0 right-0 z-50 overflow-y-auto {{ $showDetail ? '' : 'hidden' }}"
		aria-label="Task detail"
	>
		<div id="task-detail-region">
			@include('tasks._detail_panel')
		</div>
	</div>
</div>

<script>
	(() => {
		const master = document.getElementById('tasks-master');
		const detailRoot = document.getElementById('task-detail-root');
		const detailRegion = document.getElementById('task-detail-region');
		const backdrop = document.getElementById('task-detail-backdrop');
		if (!master || !detailRoot || !detailRegion || !backdrop) return;

		let inFlight = null;

		function isOpen() {
			return !detailRoot.classList.contains('hidden');
		}

		function setLayout(open) {
			backdrop.classList.toggle('hidden', !open);
			detailRoot.classList.toggle('hidden', !open);

			const isMobile = window.matchMedia('(max-width: 1023px)').matches;
			document.body.classList.toggle('overflow-hidden', open && isMobile);
		}

		function closeUrlFrom(urlString) {
			const u = new URL(urlString, window.location.origin);
			u.searchParams.delete('task');
			u.searchParams.delete('activity');
			return u.pathname + (u.search ? u.search : '') + (u.hash ? u.hash : '');
		}

		function highlightActive(task) {
			const els = master.querySelectorAll('[data-task-id]');
			els.forEach((el) => {
				el.classList.remove('bg-indigo-50');
				if (!el.classList.contains('hover:bg-slate-50')) {
					el.classList.add('hover:bg-slate-50');
				}
			});
			if (!task) return;
			const active = master.querySelector(`[data-task-id="${CSS.escape(String(task))}"]`);
			if (active) {
				active.classList.add('bg-indigo-50');
				active.classList.remove('hover:bg-slate-50');
			}
		}

		async function loadDetail(urlString) {
			if (inFlight) {
				inFlight.abort();
			}
			inFlight = new AbortController();

			detailRegion.innerHTML = `
				<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
					<div class="px-5 py-10 text-sm text-slate-500">Loading…</div>
				</div>
			`;

			const res = await fetch(urlString, {
				signal: inFlight.signal,
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
			});
			if (!res.ok) {
				throw new Error(`Failed to load: ${res.status}`);
			}
			const html = await res.text();
			const doc = new DOMParser().parseFromString(html, 'text/html');
			const next = doc.getElementById('task-detail-region');
			if (!next) {
				throw new Error('Detail region not found in response');
			}
			detailRegion.innerHTML = next.innerHTML;

			// Re-run JS initializers (Quill, activity tabs).
			document.dispatchEvent(new Event('gb:hydrate'));
			// Scroll panel to top on open, like Asana.
			detailRoot.scrollTop = 0;
		}

		async function openFromUrl(urlString, { push } = { push: true }) {
			const url = new URL(urlString, window.location.origin);
			const task = url.searchParams.get('task');
			if (!task) return;

			setLayout(true);
			highlightActive(task);

			try {
				await loadDetail(url.toString());
				if (push) {
					history.pushState({}, '', url.pathname + url.search + url.hash);
				}
			} catch (err) {
				console.error(err);
				detailRegion.innerHTML = `
					<div class="rounded-2xl border border-rose-200 bg-rose-50 shadow-sm">
						<div class="px-5 py-6 text-sm text-rose-700">Couldn’t load task details.</div>
					</div>
				`;
			}
		}

		function close({ push } = { push: true }) {
			setLayout(false);
			highlightActive(null);
			if (push) {
				history.pushState({}, '', closeUrlFrom(window.location.href));
			}
		}

		// Click outside (mobile backdrop) closes.
		backdrop.addEventListener('click', () => close({ push: true }));

		// ESC closes.
		document.addEventListener('keydown', (e) => {
			if (e.key !== 'Escape') return;
			if (!isOpen()) return;
			close({ push: true });
		});

		// Intercept clicks: open tasks without reload; close without reload.
		document.addEventListener('click', (e) => {
			const a = e.target?.closest?.('a');
			if (!a) return;
			if (a.hasAttribute('data-no-task-spa')) return;
			if (a.hasAttribute('data-task-close')) {
				e.preventDefault();
				close({ push: true });
				return;
			}
			if (e.defaultPrevented) return;
			if (e.button !== 0) return;
			if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
			if (a.target && a.target !== '') return;
			if (a.hasAttribute('download')) return;

			let url;
			try {
				url = new URL(a.href, window.location.origin);
			} catch {
				return;
			}
			if (url.origin !== window.location.origin) return;
			if (!url.pathname.endsWith('/tasks')) return;
			if (!url.searchParams.has('task')) return;

			e.preventDefault();
			openFromUrl(url.toString(), { push: true });
		});

		// Back/forward should open/close accordingly.
		window.addEventListener('popstate', () => {
			const url = new URL(window.location.href);
			const t = (url.searchParams.get('task') || '').trim();
			if (t !== '') {
				openFromUrl(url.toString(), { push: false });
			} else {
				close({ push: false });
			}
		});

		// If the page loaded with a task selected, ensure UI is hydrated.
		const initial = new URL(window.location.href);
		const initialTask = (initial.searchParams.get('task') || '').trim();
		if (initialTask !== '') {
			setLayout(true);
			highlightActive(initialTask);
			document.dispatchEvent(new Event('gb:hydrate'));
		} else {
			setLayout(false);
		}
	})();
</script>
@endsection