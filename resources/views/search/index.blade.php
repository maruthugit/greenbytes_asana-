@extends('layouts.app')
@section('content')
<div class="mb-4">
	<h1 class="text-xl font-semibold text-slate-900">Search</h1>
	<p class="mt-1 text-sm text-slate-500">Search across projects and tasks.</p>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
	<div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-6 py-4">
			<div class="text-sm font-semibold text-slate-900">Projects</div>
			@if($q)
				<div class="mt-1 text-xs text-slate-500">Results for "{{ $q }}"</div>
			@endif
		</div>
		<div class="divide-y divide-slate-100">
			@forelse($projects as $project)
				<a href="{{ route('projects.board', $project) }}" class="block px-6 py-4 hover:bg-slate-50">
					<div class="text-sm font-semibold text-slate-900">{{ $project->name }}</div>
					<div class="mt-1 text-xs text-slate-500">{{ optional($project->team)->name }}</div>
				</a>
			@empty
				<div class="px-6 py-8 text-sm text-slate-500">No project results.</div>
			@endforelse
		</div>
	</div>

	<div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-6 py-4">
			<div class="text-sm font-semibold text-slate-900">Tasks</div>
			@if($q)
				<div class="mt-1 text-xs text-slate-500">Results for "{{ $q }}"</div>
			@endif
		</div>
		<div class="divide-y divide-slate-100">
			@forelse($tasks as $task)
				<div class="px-6 py-4">
					<div class="flex items-start justify-between gap-3">
						<div class="min-w-0">
							<div class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</div>
							<div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
								@if($task->project)
									<a href="{{ route('projects.board', $task->project) }}" class="rounded-full bg-slate-100 px-2 py-0.5 hover:bg-slate-200">{{ $task->project->name }}</a>
								@endif
								@if($task->assignee)
									<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->assignee->name }}</span>
								@endif
								@if($task->due_date)
									<span class="rounded-full bg-slate-100 px-2 py-0.5">Due {{ $task->due_date->format('Y-m-d') }}</span>
								@endif
							</div>
						</div>
						<div class="shrink-0 text-xs text-slate-400">#{{ $task->id }}</div>
					</div>
				</div>
			@empty
				<div class="px-6 py-8 text-sm text-slate-500">No task results.</div>
			@endforelse
		</div>
	</div>
</div>
@endsection
