@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
	<div>
		<h1 class="text-xl font-semibold text-slate-900">Edit user</h1>
		<p class="text-sm text-slate-500">Manage roles and permissions.</p>
	</div>
	<a href="{{ route('admin.users.index') }}" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Back</a>
</div>

<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
	<form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
		@csrf
		@method('PATCH')

		<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
			<div>
				<label class="mb-1 block text-xs font-medium text-slate-600">Name</label>
				<input name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" required />
			</div>
			<div>
				<label class="mb-1 block text-xs font-medium text-slate-600">Email</label>
				<input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" required />
			</div>
			<div class="md:col-span-2">
				<label class="mb-1 block text-xs font-medium text-slate-600">New password (optional)</label>
				<input name="password" type="password" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" />
				<div class="mt-1 text-xs text-slate-500">Leave blank to keep existing password.</div>
			</div>
		</div>

		<div>
			<div class="text-sm font-semibold text-slate-900">Roles</div>
			<div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
				@foreach($roles as $role)
							@php
								$checked = in_array($role->name, $user->roles->pluck('name')->all(), true);
							@endphp
					<label class="flex items-center gap-2 rounded-2xl border border-slate-200 px-3 py-2 text-sm">
						<input type="checkbox" name="roles[]" value="{{ $role->name }}" class="rounded" @checked($checked) />
						<span class="font-medium text-slate-700">{{ $role->name }}</span>
					</label>
				@endforeach
			</div>
		</div>

		<div>
			<div class="text-sm font-semibold text-slate-900">Direct permissions</div>
			<div class="mt-1 text-xs text-slate-500">These apply in addition to role permissions.</div>
			<div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
				@foreach($permissions as $perm)
							@php
								$checked = in_array($perm->name, $user->permissions->pluck('name')->all(), true);
							@endphp
					<label class="flex items-center gap-2 rounded-2xl border border-slate-200 px-3 py-2 text-sm">
						<input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="rounded" @checked($checked) />
						<span class="font-medium text-slate-700">{{ $perm->name }}</span>
					</label>
				@endforeach
			</div>
		</div>

		<div>
			<div class="text-sm font-semibold text-slate-900">Effective permissions (read-only)</div>
			<div class="mt-1 text-xs text-slate-500">This is the final permission list after combining roles + direct permissions.</div>
			@php
				$effective = $user->getAllPermissions()->pluck('name');
				if (!$user->hasRole('admin')) {
					$effective = $effective->reject(fn ($p) => $p === 'performance.view');
				}
				$effective = $effective->sort()->values();
			@endphp
			<div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
				@if($effective->isEmpty())
					<div class="text-sm text-slate-600">No permissions assigned.</div>
				@else
					<div class="flex flex-wrap gap-2">
						@foreach($effective as $p)
							<span class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">{{ $p }}</span>
						@endforeach
					</div>
				@endif
			</div>
		</div>

		<div>
			<div class="text-sm font-semibold text-slate-900">Teams</div>
			<div class="mt-1 text-xs text-slate-500">Only users in the same team as a project can be assigned to that project’s tasks.</div>
			@php
				$selectedTeamIds = $user->teams->pluck('id')->all();
			@endphp
			<div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
				@foreach($teams as $team)
					@php
						$checked = in_array($team->id, $selectedTeamIds, true);
					@endphp
					<label class="flex items-center gap-2 rounded-2xl border border-slate-200 px-3 py-2 text-sm">
						<input type="checkbox" name="teams[]" value="{{ $team->id }}" class="rounded" @checked($checked) />
						<span class="font-medium text-slate-700">{{ $team->name }}</span>
					</label>
				@endforeach
			</div>
		</div>

		<div class="flex items-center justify-end gap-3">
			<button class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Save</button>
		</div>
	</form>
</div>
@endsection
