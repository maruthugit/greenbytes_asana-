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
				<div class="mt-2 flex items-center gap-3">
					<a href="{{ route('uploads.public', ['path' => $task->image_path], false) }}" target="_blank" class="inline-flex items-center gap-2">
					<img
						src="{{ route('uploads.public', ['path' => $task->image_path], false) }}"
						alt="Task image"
						class="h-32 w-32 rounded-xl border border-slate-200 object-cover"
						loading="lazy"
					/>
					<span class="text-sm font-medium text-indigo-700 hover:underline">Open</span>
					</a>
					<a href="{{ route('uploads.public', ['path' => $task->image_path], false) }}?download=1" class="text-sm font-medium text-slate-700 hover:underline">Download</a>
				</div>
			</div>
		@endif

		{{-- Attachments moved to top strip above Comments (Asana-style) --}}
	</div>

	<div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-5 py-3">
			<div class="text-sm font-semibold text-slate-900">Comments</div>
		</div>

		<div class="px-5 py-4" data-activity-tabs-root>
			@php
				$canManageAttachments = auth()->user()->can('tasks.manage') || auth()->user()->can('tasks.update') || auth()->user()->can('tasks.attachments.delete');
				$canAddAttachments = auth()->user()->can('tasks.manage') || auth()->user()->can('tasks.update');
				$attachments = ($task->attachments ?? collect());
			@endphp
			<div class="mb-4">
				<div class="flex items-stretch gap-3 overflow-x-auto pb-1">
					@foreach($attachments as $att)
						@include('tasks._attachment_strip_item', ['task' => $task, 'att' => $att])
					@endforeach
					@if($canAddAttachments)
						<form method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data" class="shrink-0">
							@csrf
							<input id="task-attachments-strip-upload-{{ $task->id }}" type="file" name="attachments[]" multiple class="hidden" onchange="this.form.submit()" />
							<label for="task-attachments-strip-upload-{{ $task->id }}" class="inline-flex h-full w-16 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white text-slate-500 hover:bg-slate-50 cursor-pointer">
								<span class="text-2xl leading-none">+</span>
								<span class="sr-only">Add attachments</span>
							</label>
						</form>
					@endif
				</div>
			</div>
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
							'meta' => $c->meta,
						]);
					}
				}
				$activity = $activity->sortBy('at');
				$attachmentsById = ($task->attachments ?? collect())->keyBy('id');
				$commentFeed = collect();
				foreach (($task->activities ?? collect()) as $a) {
					if ((string) $a->type === 'attachments.added') {
						$commentFeed = $commentFeed->push([
							'type' => 'attached',
							'at' => $a->created_at,
							'user' => $a->user,
							'meta' => $a->meta ?? [],
						]);
					}
				}
				if ($canViewComments) {
					foreach ($task->comments as $c) {
						$commentFeed = $commentFeed->push([
							'type' => 'comment',
							'at' => $c->created_at,
							'user' => $c->user,
							'body' => $c->body,
							'meta' => $c->meta,
						]);
					}
				}
				$commentFeed = $commentFeed->sortBy('at');
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
						@forelse($commentFeed as $item)
							@php
								$author = $item['user'] ?? null;
								$at = $item['at'] ?? null;
								$t = (string) ($item['type'] ?? '');
							@endphp
							<div class="flex items-start gap-3">
								<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-semibold text-sky-800">
									{{ $initials($author?->name) }}
								</div>
								<div class="min-w-0 flex-1">
									<div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
										<div class="text-sm font-semibold text-slate-900">{{ $author?->name ?? 'Unknown' }}</div>
										@if($t === 'comment')
											<div class="text-xs text-slate-500">{{ $at?->format('M j, Y g:i A') }}</div>
										@else
											<div class="text-sm text-slate-500">attached</div>
											<div class="text-xs text-slate-500">· {{ $at?->format('M j, Y g:i A') }}</div>
										@endif
									</div>
									@if($t === 'comment')
										@php
											$body = (string) ($item['body'] ?? '');
											$isHtml = str_contains($body, '<') && str_contains($body, '>');
											$meta = (array) ($item['meta'] ?? []);
											$ids = (array) ($meta['attachment_ids'] ?? []);
											$atts = collect($ids)->map(fn ($id) => $attachmentsById->get((int) $id))->filter();
										@endphp
										@if($isHtml)
											<div class="richtext-content mt-1 text-sm text-slate-700">{!! $body !!}</div>
										@else
											<div class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $body }}</div>
										@endif
										@if($atts->count())
											<div class="mt-2 space-y-2">
												@foreach($atts as $att)
													@include('tasks._attachment_card', ['task' => $task, 'att' => $att, 'showDelete' => $canManageAttachments, 'showUploaded' => false])
												@endforeach
											</div>
										@endif
									@else
										@php
											$meta = (array) ($item['meta'] ?? []);
											$ids = (array) ($meta['attachment_ids'] ?? []);
											$atts = collect($ids)->map(fn ($id) => $attachmentsById->get((int) $id))->filter();
										@endphp
										@if($atts->count())
											<div class="mt-2 space-y-2">
												@foreach($atts as $att)
													@include('tasks._attachment_card', ['task' => $task, 'att' => $att, 'showDelete' => $canManageAttachments, 'showUploaded' => false])
												@endforeach
											</div>
										@endif
									@endif
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
					<form method="POST" action="{{ route('tasks.comments.store', $task) }}" enctype="multipart/form-data" class="mt-5">
						@csrf
						@php
							$notifyName = $task->assignee?->name;
						@endphp
						<div class="flex items-start gap-3">
							<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700">
								{{ $initials(auth()->user()->name ?? '') }}
							</div>
							<div class="min-w-0 flex-1">
								<div class="rounded-xl border border-slate-300 bg-white comment-composer" data-comment-composer data-upload-url="{{ route('uploads.richtext') }}">
									<textarea name="body" class="hidden" required></textarea>
									<div class="[&_.ql-toolbar]:hidden [&_.ql-container]:border-0 [&_.ql-editor]:min-h-[110px] [&_.ql-editor]:px-4 [&_.ql-editor]:py-3 [&_.ql-editor]:text-sm" data-comment-editor></div>
									<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-3 py-2">
										<div class="flex items-center gap-1 text-slate-500">
											<button type="button" class="rounded-md p-2 hover:bg-slate-100" title="Format" data-comment-action="format">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
													<path d="M5.5 4A1.5 1.5 0 0 1 7 2.5h6A1.5 1.5 0 0 1 14.5 4v1a.75.75 0 0 1-1.5 0V4H7v1a.75.75 0 0 1-1.5 0V4Z" />
													<path d="M7.25 7a.75.75 0 0 1 .75-.75h4a.75.75 0 0 1 0 1.5h-1.25V16a.75.75 0 0 1-1.5 0V7.75H8a.75.75 0 0 1-.75-.75Z" />
												</svg>
											</button>
											<button type="button" class="rounded-md p-2 hover:bg-slate-100" title="Emoji" data-comment-action="emoji">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
													<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.5-7a.75.75 0 0 1 .75.75 4.25 4.25 0 0 1-8.5 0 .75.75 0 0 1 1.5 0 2.75 2.75 0 0 0 5.5 0 .75.75 0 0 1 .75-.75ZM7.5 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" clip-rule="evenodd" />
												</svg>
											</button>
											<button type="button" class="rounded-md p-2 hover:bg-slate-100" title="Mention" data-comment-action="mention">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
													<path d="M10 2.5a7.5 7.5 0 1 0 4.49 13.5.75.75 0 0 0-.9-1.2A6 6 0 1 1 16 10a2.5 2.5 0 0 1-5 0V8.5a3.5 3.5 0 1 0-1.5 2.88V10a.75.75 0 0 0-1.5 0 2 2 0 1 1 4 0v.25A4 4 0 1 0 16 10a6 6 0 0 0-6-6Z" />
												</svg>
											</button>
											<button type="button" class="rounded-md p-2 hover:bg-slate-100" title="Follow" data-comment-action="follow">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
													<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.539 1.118l-2.8-2.034a1 1 0 0 0-1.176 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.88 8.809c-.783-.57-.38-1.81.588-1.81H6.93a1 1 0 0 0 .95-.69l1.07-3.292Z" />
												</svg>
											</button>
											<button type="button" class="rounded-md p-2 hover:bg-slate-100" title="Attach" data-comment-action="attach">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
													<path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.243 0l-6.2 6.2a4 4 0 1 0 5.657 5.657l6.2-6.2a2 2 0 1 0-2.829-2.828l-5.657 5.657a1 1 0 1 0 1.414 1.414l5.657-5.657a.5.5 0 1 1 .707.707l-6.2 6.2a2.5 2.5 0 0 1-3.536-3.536l6.2-6.2a1.5 1.5 0 1 1 2.121 2.121l-6.2 6.2a.75.75 0 0 0 1.06 1.06l6.2-6.2a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" />
												</svg>
											</button>
											<button type="button" class="rounded-md p-2 hover:bg-slate-100" title="AI" data-comment-action="ai">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
													<path d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.132 2.727a1 1 0 0 1-.53.53L4.743 7.273c-.772.321-.772 1.415 0 1.736l2.727 1.132a1 1 0 0 1 .53.53l1.132 2.727c.321.772 1.415.772 1.736 0l1.132-2.727a1 1 0 0 1 .53-.53l2.727-1.132c.772-.321.772-1.415 0-1.736l-2.727-1.132a1 1 0 0 1-.53-.53l-1.132-2.727Z" />
													<path d="M5 16a1 1 0 0 1 1-1h1v-1a1 1 0 1 1 2 0v1h1a1 1 0 1 1 0 2H9v1a1 1 0 1 1-2 0v-1H6a1 1 0 0 1-1-1Z" />
												</svg>
											</button>
										</div>
										<div class="flex items-center gap-3">
											<div class="hidden text-xs text-slate-500 sm:block" data-comment-notify>
												{{ $notifyName ? ($notifyName . ' will be notified') : 'People on this task will be notified' }}
											</div>
											<button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Comment</button>
									</div>
								</div>
									<input type="file" name="attachments[]" multiple class="hidden" data-comment-attachments-input />
									<div class="hidden px-3 pb-3" data-comment-attachments-preview></div>
									<div class="hidden px-3 pb-3" data-comment-formatbar>
										<div class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1">
											<button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100" data-comment-format="bold">B</button>
											<button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100" data-comment-format="italic">I</button>
											<button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100" data-comment-format="underline">U</button>
											<button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100" data-comment-format="bullet">• List</button>
											<button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100" data-comment-format="ordered">1. List</button>
											<button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100" data-comment-format="link">Link</button>
										</div>
									</div>
									<div class="hidden px-3 pb-3" data-comment-emoji-popover>
										<div class="inline-flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-white p-2">
											@foreach(['😀','😅','😂','🙂','😉','😍','👍','👏','🙏','🎉','✅','❗','🔥','💡','📎','⭐'] as $em)
												<button type="button" class="h-9 w-9 rounded-md text-lg hover:bg-slate-100" data-comment-emoji="{{ $em }}">{{ $em }}</button>
											@endforeach
										</div>
									</div>
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
										<span class="text-slate-500">· {{ $at?->format('M j, Y g:i A') }}</span>
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
										} elseif ($ct === 'attachments.added') {
											$msg = 'attached';
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
											<span class="text-slate-500">· {{ $at->format('M j, Y g:i A') }}</span>
										@endif
									</div>
									@if($ct === 'attachments.added')
										@php
											$ids = (array) ($meta['attachment_ids'] ?? []);
											$atts = collect($ids)->map(fn ($id) => $attachmentsById->get((int) $id))->filter();
										@endphp
										@if($atts->count())
											<div class="mt-2 space-y-2">
												@foreach($atts as $att)
													@include('tasks._attachment_card', ['task' => $task, 'att' => $att, 'showDelete' => $canManageAttachments, 'showUploaded' => false])
												@endforeach
											</div>
										@endif
									@endif
								@else
									<div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
										<div class="text-sm font-semibold text-slate-900">{{ $user?->name ?? 'Unknown' }}</div>
										<div class="text-xs text-slate-500">{{ $at?->format('M j, Y g:i A') }}</div>
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
