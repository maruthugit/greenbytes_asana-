@extends('layouts.app')
@section('content')
@php
	$hour = now()->hour;
	$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
	$todayLabel = now()->format('l, F j');
	$tab = $tab ?? 'upcoming';
	$counts = $counts ?? ['upcoming' => 0, 'overdue' => 0, 'completed' => 0];
	$activeTabClass = 'border-indigo-600 text-slate-900';
	$inactiveTabClass = 'border-transparent text-slate-500 hover:text-slate-700';
@endphp

<div class="mb-6">
	<div class="text-sm text-slate-500">{{ $todayLabel }}</div>
	<h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{{ $greeting }}, {{ auth()->user()->name }}</h1>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
	<div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
		<div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
			<div class="text-base font-semibold text-slate-900">My tasks</div>
			@canany(['tasks.view', 'tasks.manage'])
				<a href="/tasks" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Open</a>
			@endcanany
		</div>

		<div class="px-6 pt-3">
			<div class="flex items-center gap-6 border-b border-slate-100">
				<a href="/?tab=upcoming" class="-mb-px border-b-2 pb-3 text-sm font-semibold {{ $tab === 'upcoming' ? $activeTabClass : $inactiveTabClass }}">
					Upcoming <span class="ml-1 text-xs font-medium text-slate-400">({{ $counts['upcoming'] ?? 0 }})</span>
				</a>
				<a href="/?tab=overdue" class="-mb-px border-b-2 pb-3 text-sm font-semibold {{ $tab === 'overdue' ? $activeTabClass : $inactiveTabClass }}">
					Overdue <span class="ml-1 text-xs font-medium text-slate-400">({{ $counts['overdue'] ?? 0 }})</span>
				</a>
				<a href="/?tab=completed" class="-mb-px border-b-2 pb-3 text-sm font-semibold {{ $tab === 'completed' ? $activeTabClass : $inactiveTabClass }}">
					Completed <span class="ml-1 text-xs font-medium text-slate-400">({{ $counts['completed'] ?? 0 }})</span>
				</a>
			</div>

			@canany(['tasks.manage', 'tasks.create'])
				<div class="py-3">
					<a href="/tasks?task=new" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
						<span class="text-slate-500">+</span>
						Create task
					</a>
				</div>
			@endcanany
		</div>

		<div class="divide-y divide-slate-100">
			@forelse($tasks as $task)
				<div class="px-6 py-4">
					<div class="flex items-start justify-between gap-3">
						<div class="min-w-0">
							<div class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</div>
							<div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
								@if($task->project)
									@canany(['projects.view', 'projects.manage'])
										<a href="{{ route('projects.board', $task->project) }}" class="rounded-full bg-slate-100 px-2 py-0.5 hover:bg-slate-200">
											{{ $task->project->name }}
										</a>
									@else
										<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->project->name }}</span>
									@endcanany
								@endif
								@if($task->due_date)
									<span class="rounded-full bg-slate-100 px-2 py-0.5">Due {{ $task->due_date->format('Y-m-d') }}</span>
								@else
									<span class="rounded-full bg-slate-100 px-2 py-0.5">No due date</span>
								@endif
							</div>
						</div>
						<div class="shrink-0 text-xs text-slate-400">#{{ $task->id }}</div>
					</div>
				</div>
			@empty
				<div class="px-6 py-10 text-sm text-slate-500">No tasks found in this tab.</div>
			@endforelse
		</div>
	</div>

	<div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
		<div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
			<div class="text-base font-semibold text-slate-900">Projects</div>
			@canany(['projects.view', 'projects.manage'])
				<a href="/projects" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Browse</a>
			@endcanany
		</div>

		<div class="p-6">
			<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
				@forelse($projects as $project)
					<a href="{{ route('projects.board', $project) }}" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-slate-300 hover:shadow">
						<div class="flex items-center gap-3">
							<div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700">
								<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M4 7a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7Z" stroke="currentColor" stroke-width="2"/>
									<path d="M8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
								</svg>
							</div>
							<div class="min-w-0">
								<div class="truncate text-sm font-semibold text-slate-900 group-hover:text-slate-950">{{ $project->name }}</div>
								<div class="mt-0.5 truncate text-xs text-slate-500">
									@if($project->team)
										{{ $project->team->name }}
									@endif
								</div>
							</div>
						</div>
					</a>
				@empty
					<div class="text-sm text-slate-500">No projects yet. Create one from the Projects page.</div>
				@endforelse
			</div>
		</div>
	</div>
</div>
@endsection