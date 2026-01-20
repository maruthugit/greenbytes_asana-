@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
	<div>
		<h1 class="text-xl font-semibold text-slate-900">Task</h1>
		<p class="text-sm text-slate-500">View details and comments.</p>
	</div>
	<div class="flex items-center gap-2">
		@canany(['tasks.manage', 'tasks.delete'])
			@role('admin')
				<form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task? This cannot be undone.');">
					@csrf
					@method('DELETE')
					<input type="hidden" name="redirect" value="/tasks" />
					<button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
				</form>
			@endrole
		@endcanany
		<a href="/tasks" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Back</a>
	</div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
	<div class="lg:col-span-1 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
		<div class="text-xs font-medium text-slate-500">Title</div>
		<div class="mt-1 text-base font-semibold text-slate-900">{{ $task->title }}</div>

		@if(!empty($task->description))
			<div class="mt-4">
				<div class="text-xs font-medium text-slate-500">Description</div>
				<div class="richtext-content mt-2 text-sm text-slate-700">{!! $task->description !!}</div>
			</div>
		@endif

		<div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
			<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->status }}</span>
			@if($task->project)
				<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->project->name }}</span>
			@endif
			@if($task->project && $task->project->team)
				<span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->project->team->name }}</span>
			@endif
			@if($task->assignee)
				<span class="rounded-full bg-slate-100 px-2 py-0.5">Assignee: {{ $task->assignee->name }}</span>
			@endif
			@if($task->due_date)
				<span class="rounded-full bg-slate-100 px-2 py-0.5">Due {{ $task->due_date->format('Y-m-d') }}</span>
			@endif
		</div>

		@if($task->image_path)
			<div class="mt-4">
				<div class="text-xs font-medium text-slate-500">Image</div>
				<a href="{{ \Illuminate\Support\Facades\Storage::url($task->image_path) }}" target="_blank" class="mt-2 inline-flex items-center gap-2">
					<img
						src="{{ \Illuminate\Support\Facades\Storage::url($task->image_path) }}"
						alt="Task image"
						class="h-32 w-32 rounded-xl border border-slate-200 object-cover"
						loading="lazy"
					/>
					<span class="text-sm font-medium text-indigo-700 hover:underline">Open</span>
				</a>
			</div>
		@endif
	</div>

	<div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-5 py-3">
			<div class="text-sm font-semibold text-slate-900">Comments</div>
		</div>

		<div class="px-5 py-4" data-activity-tabs-root>
			@php
				$canViewComments = auth()->user()->can('comments.view') || auth()->user()->can('comments.manage');
				$initials = function (?string $name): string {
					$name = trim((string) $name);
					if ($name === '') return '?';
					$parts = preg_split('/\s+/', $name) ?: [];
					$first = mb_substr($parts[0] ?? $name, 0, 1);
					$last = mb_substr($parts[count($parts) - 1] ?? $name, 0, 1);
					$out = mb_strtoupper($first . ($last !== $first ? $last : ''));
					return $out !== '' ? $out : '?';
				};
				$activity = collect();
				$activity = $activity->push([
					'type' => 'created',
					'at' => $task->created_at,
					'user' => $task->creator ?? null,
				]);
				foreach (($task->activities ?? collect()) as $a) {
					$activity = $activity->push([
						'type' => 'change',
						'at' => $a->created_at,
						'user' => $a->user,
						'change_type' => $a->type,
						'meta' => $a->meta ?? [],
					]);
				}
				if ($canViewComments) {
					foreach ($task->comments as $c) {
						$activity = $activity->push([
							'type' => 'comment',
							'at' => $c->created_at,
							'user' => $c->user,
							'body' => $c->body,
						]);
					}
				}
				$activity = $activity->sortBy('at');
			@endphp

			<div class="flex items-center justify-between gap-3">
				<div class="flex items-center gap-4 border-b border-slate-200">
					<button type="button" data-activity-tab="comments" class="-mb-px border-b-2 border-slate-900 px-1 pb-2 text-sm font-semibold text-slate-900">Comments</button>
					<button type="button" data-activity-tab="activity" class="-mb-px border-b-2 border-transparent px-1 pb-2 text-sm font-semibold text-slate-500 hover:text-slate-700">All activity</button>
				</div>
				<div class="text-xs text-slate-400">Oldest</div>
			</div>

			<div class="mt-4" data-activity-panel="comments">
				@if($canViewComments)
					<div class="space-y-4">
						@forelse($task->comments as $comment)
							@php $author = $comment->user; @endphp
							<div class="flex items-start gap-3">
								<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-semibold text-sky-800">
									{{ $initials($author?->name) }}
								</div>
								<div class="min-w-0 flex-1">
									<div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
										<div class="text-sm font-semibold text-slate-900">{{ $author?->name ?? 'Unknown' }}</div>
										<div class="text-xs text-slate-500">{{ $comment->created_at?->diffForHumans() }}</div>
									</div>
									<div class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $comment->body }}</div>
								</div>
							</div>
						@empty
							<div class="text-sm text-slate-500">No comments yet.</div>
						@endforelse
					</div>
				@else
					<div class="text-sm text-slate-500">You don’t have permission to view comments.</div>
				@endif

				@canany(['comments.manage', 'comments.create'])
					<form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="mt-5">
						@csrf
						<div class="flex items-start gap-3">
							<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700">
								{{ $initials(auth()->user()->name ?? '') }}
							</div>
							<div class="min-w-0 flex-1">
								<textarea name="body" rows="3" placeholder="Add a comment" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" required></textarea>
								<div class="mt-2 flex items-center justify-end">
									<button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Comment</button>
								</div>
							</div>
						</div>
					</form>
				@endcanany
			</div>

			<div class="mt-4" data-activity-panel="activity" style="display:none">
				<div class="space-y-4">
					@foreach($activity as $evt)
						@php
							$user = $evt['user'] ?? null;
							$at = $evt['at'] ?? null;
						@endphp
						<div class="flex items-start gap-3">
							<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700">
								{{ $initials($user?->name) }}
							</div>
							<div class="min-w-0 flex-1">
								@if(($evt['type'] ?? '') === 'created')
									<div class="text-sm text-slate-700">
										<span class="font-semibold text-slate-900">{{ $user?->name ?? 'Someone' }}</span>
										created this task
										<span class="text-slate-500">· {{ $at?->format('M j, Y') }}</span>
									</div>
								@elseif(($evt['type'] ?? '') === 'change')
									@php
										$ct = (string) ($evt['change_type'] ?? '');
										$meta = (array) ($evt['meta'] ?? []);
										$fmtDate = function ($value): ?string {
											$value = (string) ($value ?? '');
											if ($value === '') return null;
											try {
												return \Illuminate\Support\Carbon::parse($value)->format('M j, Y');
											} catch (\Throwable $e) {
												return $value;
											}
										};
										$msg = '';
										if ($ct === 'status.changed') {
											$msg = 'changed status from ' . ($meta['from'] ?? '…') . ' to ' . ($meta['to'] ?? '…');
										} elseif ($ct === 'assignee.changed') {
											$fromName = (string) ($meta['from_name'] ?? '');
											$toName = (string) ($meta['to_name'] ?? '');
											$from = $fromName !== '' ? $fromName : (($meta['from'] ?? null) ? ('User #' . $meta['from']) : 'Unassigned');
											$to = $toName !== '' ? $toName : (($meta['to'] ?? null) ? ('User #' . $meta['to']) : 'Unassigned');
											if (($meta['from'] ?? null) && !($meta['to'] ?? null)) {
												$msg = 'unassigned (was ' . $from . ')';
											} elseif (!($meta['from'] ?? null) && ($meta['to'] ?? null)) {
												$msg = 'assigned to ' . $to;
											} else {
												$msg = 'changed assignee from ' . $from . ' to ' . $to;
											}
										} elseif ($ct === 'due_date.changed') {
											$from = $fmtDate($meta['from'] ?? null);
											$to = $fmtDate($meta['to'] ?? null);
											if ($from && !$to) {
												$msg = 'removed due date (was ' . $from . ')';
											} elseif (!$from && $to) {
												$msg = 'set due date to ' . $to;
											} else {
												$msg = 'changed due date from ' . ($from ?? '…') . ' to ' . ($to ?? '…');
											}
										} elseif ($ct === 'description.updated') {
											$msg = 'updated description';
										} elseif ($ct === 'attachment.removed') {
											$msg = 'removed an attachment';
										} elseif ($ct === 'image.removed') {
											$msg = 'removed the task image';
										} else {
											$msg = 'updated task';
										}
									@endphp
									<div class="text-sm text-slate-700">
										<span class="font-semibold text-slate-900">{{ $user?->name ?? 'Someone' }}</span>
										{{ $msg }}
										@if($at)
											<span class="text-slate-500">· {{ $at->diffForHumans() }}</span>
										@endif
									</div>
								@else
									<div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
										<div class="text-sm font-semibold text-slate-900">{{ $user?->name ?? 'Unknown' }}</div>
										<div class="text-xs text-slate-500">{{ $at?->diffForHumans() }}</div>
									</div>
									<div class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $evt['body'] ?? '' }}</div>
								@endif
							</div>
						</div>
					@endforeach
				</div>
			</div>

			<script>
				(() => {
					const root = document.currentScript?.closest('.px-5');
					if (!root) return;
					const buttons = root.querySelectorAll('[data-activity-tab]');
					const panels = root.querySelectorAll('[data-activity-panel]');
					if (!buttons.length || !panels.length) return;

					function setTab(tab) {
						panels.forEach((p) => {
							p.style.display = p.getAttribute('data-activity-panel') === tab ? '' : 'none';
						});
						buttons.forEach((b) => {
							const active = b.getAttribute('data-activity-tab') === tab;
							b.classList.toggle('border-slate-900', active);
							b.classList.toggle('text-slate-900', active);
							b.classList.toggle('border-transparent', !active);
							b.classList.toggle('text-slate-500', !active);
						});
					}

					buttons.forEach((b) => {
						b.addEventListener('click', () => setTab(b.getAttribute('data-activity-tab')));
					});

					setTab('comments');
				})();
			</script>
		</div>
	</div>
</div>
@endsection
