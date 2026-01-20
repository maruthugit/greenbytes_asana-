@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
	<div>
		<h1 class="text-xl font-semibold text-slate-900">Users</h1>
		<p class="text-sm text-slate-500">Create users and assign roles.</p>
	</div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
	<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
		<div class="text-sm font-semibold text-slate-900">Create user</div>
		<form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 space-y-3">
			@csrf
			<div>
				<label class="mb-1 block text-xs font-medium text-slate-600">Name</label>
				<input name="name" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" required />
			</div>
			<div>
				<label class="mb-1 block text-xs font-medium text-slate-600">Email</label>
				<input name="email" type="email" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" required />
			</div>
			<div>
				<label class="mb-1 block text-xs font-medium text-slate-600">Password</label>
				<input name="password" type="password" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" required />
			</div>
			<div>
				<label class="mb-1 block text-xs font-medium text-slate-600">Role</label>
				<select name="role" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
					<option value="">(none)</option>
					@foreach($roles as $role)
						<option value="{{ $role->name }}">{{ $role->name }}</option>
					@endforeach
				</select>
			</div>
			<div>
				<label class="mb-1 block text-xs font-medium text-slate-600">Teams (for task assignment)</label>
				<select name="teams[]" multiple class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
					@foreach($teams as $team)
						<option value="{{ $team->id }}">{{ $team->name }}</option>
					@endforeach
				</select>
				<div class="mt-1 text-xs text-slate-500">Users must be in a team to appear in the Assignee dropdown for that team's projects.</div>
			</div>
			<button class="w-full rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Create</button>
		</form>
	</div>

	<div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-6 py-4">
			<div class="text-sm font-semibold text-slate-900">All users</div>
		</div>
		<div class="divide-y divide-slate-100">
			@foreach($users as $u)
				<a href="{{ route('admin.users.edit', $u) }}" class="block px-6 py-4 hover:bg-slate-50">
					<div class="flex flex-wrap items-center justify-between gap-3">
						<div class="min-w-0">
							<div class="truncate text-sm font-semibold text-slate-900">{{ $u->name }}</div>
							<div class="mt-1 text-xs text-slate-500">{{ $u->email }}</div>
							<div class="mt-2 flex flex-wrap gap-2">
								@forelse($u->roles as $r)
									<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $r->name }}</span>
								@empty
									<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">no role</span>
								@endforelse
							</div>
						</div>
						<div class="text-xs text-slate-400">Edit</div>
					</div>
				</a>
			@endforeach
		</div>
	</div>
</div>
@endsection
