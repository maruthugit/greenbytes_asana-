@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
	<div>
		<h1 class="text-xl font-semibold text-slate-900">Teams</h1>
		<p class="text-sm text-slate-500">Group people and projects.</p>
	</div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
	@canany(['teams.manage', 'teams.create'])
		<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
			<div class="text-sm font-semibold text-slate-900">Create team</div>
			<form method="POST" action="/teams" class="mt-3 space-y-3">
				@csrf
				<input name="name" placeholder="Team name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
				<button class="w-full rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Add team</button>
			</form>
		</div>
	@endcanany

	<div class="{{ auth()->user()->can('teams.manage') || auth()->user()->can('teams.create') ? 'lg:col-span-2' : 'lg:col-span-3' }} rounded-2xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-5 py-3">
			<div class="text-sm font-semibold text-slate-900">Your teams</div>
		</div>
		<div class="divide-y divide-slate-100">
			@forelse($teams as $team)
				<div class="flex items-center justify-between px-5 py-4">
					<div class="min-w-0">
						<div class="truncate text-sm font-semibold text-slate-900">{{ $team->name }}</div>
						<div class="mt-1 text-xs text-slate-500">Team ID: {{ $team->id }}</div>
					</div>
					@canany(['teams.manage', 'teams.update', 'teams.delete'])
						<div class="flex shrink-0 items-center gap-2">
							@canany(['teams.manage', 'teams.update'])
								<a href="{{ route('teams.edit', $team) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Edit</a>
							@endcanany
							@role('admin')
								<form method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Delete this team? This cannot be undone.');">
									@csrf
									@method('DELETE')
									<button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
								</form>
							@endrole
						</div>
					@endcanany
				</div>
			@empty
				<div class="px-5 py-8 text-sm text-slate-500">No teams yet.</div>
			@endforelse
		</div>
	</div>
</div>
@endsection