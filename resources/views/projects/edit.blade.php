@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
	<div>
		<h1 class="text-xl font-semibold text-slate-900">Edit project</h1>
		<p class="text-sm text-slate-500">Update project details.</p>
	</div>
	<div class="flex items-center gap-2">
		<a href="{{ route('projects.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Back</a>
	</div>
</div>

<div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
	<form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-3">
		@csrf
		@method('PATCH')

		<label class="block text-xs font-medium text-slate-600">Project name</label>
		<input name="name" value="{{ old('name', $project->name) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />

		<label class="block text-xs font-medium text-slate-600">Description</label>
		<textarea name="description" rows="4" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none">{{ old('description', $project->description) }}</textarea>

		<div class="flex items-center gap-2 pt-2">
			<button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save</button>
			<a href="{{ route('projects.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Cancel</a>
		</div>
	</form>

	@role('admin')
		<div class="mt-5 border-t border-slate-200 pt-4">
			<form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project? This cannot be undone.');">
				@csrf
				@method('DELETE')
				<button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Delete project</button>
			</form>
			<p class="mt-2 text-xs text-slate-500">Deletion is blocked if the project still has tasks.</p>
		</div>
	@endrole
</div>
@endsection
