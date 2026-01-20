@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
	<div>
		<h1 class="text-xl font-semibold text-slate-900">Projects</h1>
		<p class="text-sm text-slate-500">Projects live inside teams.</p>
	</div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
	@canany(['projects.manage', 'projects.create'])
		<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
			<div class="text-sm font-semibold text-slate-900">Create project</div>
			<form method="POST" action="/projects" class="mt-3 space-y-3">
				@csrf
				<label class="block text-xs font-medium text-slate-600">Team</label>
				<select name="team_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none">
					@foreach($teams as $team)
						<option value="{{ $team->id }}">{{ $team->name }}</option>
					@endforeach
				</select>
				<input name="name" placeholder="Project name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
				<input name="description" placeholder="Description (optional)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
				<button class="w-full rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Add project</button>
			</form>
		</div>
	@endcanany

	<div class="{{ auth()->user()->can('projects.manage') || auth()->user()->can('projects.create') ? 'lg:col-span-2' : 'lg:col-span-3' }} rounded-2xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-5 py-3">
			<div class="text-sm font-semibold text-slate-900">Your projects</div>
		</div>
		<div class="divide-y divide-slate-100">
			@forelse($projects as $project)
				<div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
					<div class="min-w-0">
						<div class="truncate text-sm font-semibold text-slate-900">{{ $project->name }}</div>
						<div class="mt-1 text-xs text-slate-500">
							@if($project->team)
								Team: {{ $project->team->name }}
							@endif
						</div>
					</div>
					<div class="flex items-center gap-2">
						<a href="{{ route('projects.board', $project) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Open board</a>
						@canany(['projects.manage', 'projects.update', 'projects.delete'])
							@canany(['projects.manage', 'projects.update'])
								<a href="{{ route('projects.edit', $project) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Edit</a>
							@endcanany
							@role('admin')
								<form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project? This cannot be undone.');">
									@csrf
									@method('DELETE')
									<button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
								</form>
							@endrole
						@endcanany
					</div>
				</div>
			@empty
				<div class="px-5 py-8 text-sm text-slate-500">No projects yet.</div>
			@endforelse
		</div>
	</div>
</div>
@endsection