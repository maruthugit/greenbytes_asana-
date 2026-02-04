<div
	class="rounded-2xl border border-slate-200 shadow-xl"
	style="background: rgba(255,255,255,0.94); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);"
>
	<div class="border-b border-slate-200 px-5 py-3">
		<div class="text-sm font-semibold text-slate-900">Task details</div>
	</div>

	@php
		$closeParams = request()->query();
		unset($closeParams['task'], $closeParams['activity']);
		$closeUrl = url('/tasks') . (count($closeParams) ? ('?' . http_build_query($closeParams)) : '');
	@endphp

	@if(($selectedMode ?? 'none') === 'new')
		<div class="px-5 py-5">
			<div class="flex flex-wrap items-center justify-between gap-3">
				<div class="text-lg font-semibold text-slate-900">Create task</div>
				<a href="{{ $closeUrl }}" data-task-close class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50" aria-label="Close">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
						<path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
					</svg>
				</a>
			</div>

			@canany(['tasks.manage', 'tasks.create'])
				<form method="POST" action="/tasks" enctype="multipart/form-data" class="mt-4 space-y-4">
					@csrf
					<input type="hidden" name="status" value="Todo" />

					<div>
						<label class="mb-1 block text-xs font-medium text-slate-600">Task name</label>
						<input name="title" placeholder="Write a task name" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-base focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" required />
					</div>

					<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
						<div>
							<label class="mb-1 block text-xs font-medium text-slate-600">Assignee</label>
							<select name="assigned_to" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
								<option value="">Unassigned</option>
								@foreach($users as $user)
									<option value="{{ $user->id }}">{{ $user->name }}</option>
								@endforeach
							</select>
						</div>
						<div>
							<label class="mb-1 block text-xs font-medium text-slate-600">Due date</label>
							<input name="due_date" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" />
						</div>
						<div class="md:col-span-2">
							<label class="mb-1 block text-xs font-medium text-slate-600">Project</label>
							<select name="project_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
								@foreach($projects as $project)
									<option value="{{ $project->id }}">{{ $project->name }}</option>
								@endforeach
							</select>
						</div>
					</div>

					<div>
						<label class="mb-1 block text-xs font-medium text-slate-600">Description</label>
						<div class="richtext" data-richtext data-upload-url="{{ route('uploads.richtext') }}">
							<textarea name="description" class="hidden">{{ old('description', '') }}</textarea>
							<div
								data-richtext-editor
								data-initial='@json(old('description', ''))'
								class="bg-white"
							></div>
						</div>
					</div>

					<div>
						<label class="mb-1 block text-xs font-medium text-slate-600">Image (optional)</label>
						<input name="image" type="file" accept="image/*" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
					</div>

					<div data-attachments-picker>
						<label class="mb-1 block text-xs font-medium text-slate-600">Attachments (optional)</label>
						<div class="mt-2 flex flex-wrap items-center gap-2">
							<input
								name="attachments[]"
								type="file"
								multiple
								class="hidden"
								data-attachments-input
							/>
							<label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-attachments-button>
								<span class="text-lg">+</span>
								Add attachments
							</label>
							<button type="button" class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-attachments-clear>Clear</button>
						</div>
						<div class="mt-1 text-xs text-slate-500" data-attachments-help>You can select multiple files.</div>
						<div class="mt-3 hidden grid grid-cols-2 gap-3 sm:grid-cols-3" data-attachments-preview></div>
					</div>

					<div class="flex items-center justify-end gap-3">
						<button class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create task</button>
					</div>
				</form>
			@else
				<div class="mt-4 text-sm text-slate-500">You don’t have permission to create tasks.</div>
			@endcanany
		</div>
	@elseif(!empty($selectedTask))
		<div class="px-5 py-5">
			<div class="flex flex-wrap items-center justify-between gap-3">
				<div class="min-w-0">
					<div class="text-lg font-semibold text-slate-900">{{ $selectedTask->title }}</div>
					<div class="mt-1 text-xs text-slate-500">Created {{ $selectedTask->created_at?->diffForHumans() }}</div>
				</div>
				<div class="flex items-center gap-2">
					<a href="{{ $closeUrl }}" data-task-close class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
							<path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
						</svg>
					</a>
					@canany(['tasks.manage', 'tasks.update', 'tasks.complete'])
						@if($selectedTask->status !== 'Done')
							<form method="POST" action="{{ route('tasks.complete', $selectedTask) }}">
								@csrf
								<button class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Mark complete</button>
							</form>
						@endif
						@role('admin')
							<form method="POST" action="{{ route('tasks.destroy', $selectedTask) }}" onsubmit="return confirm('Delete this task? This cannot be undone.');">
								@csrf
								@method('DELETE')
								<input type="hidden" name="redirect" value="{{ $closeUrl }}" />
								<button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
							</form>
						@endrole
					@endcanany
					<a href="{{ route('tasks.show', $selectedTask) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Open</a>
				</div>
			</div>

			@canany(['tasks.manage', 'tasks.update'])
				<form method="POST" action="{{ route('tasks.update', $selectedTask) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
					@csrf
					@method('PATCH')

					<div>
						<label class="mb-1 block text-xs font-medium text-slate-600">Task name</label>
						<input name="title" value="{{ old('title', $selectedTask->title) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-base focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" required />
					</div>

					<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
						<div>
							<label class="mb-1 block text-xs font-medium text-slate-600">Assignee</label>
							<select name="assigned_to" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
								<option value="">Unassigned</option>
								@if(!empty($selectedTask->assignee) && !$users->contains('id', $selectedTask->assignee->id))
									<option value="{{ $selectedTask->assignee->id }}" @selected((string) old('assigned_to', $selectedTask->assigned_to) === (string) $selectedTask->assignee->id)>
										{{ $selectedTask->assignee->name }} (current)
									</option>
								@endif
								@foreach($users as $user)
									<option value="{{ $user->id }}" @selected((string) old('assigned_to', $selectedTask->assigned_to) === (string) $user->id)>{{ $user->name }}</option>
								@endforeach
							</select>
						</div>
						<div>
							<label class="mb-1 block text-xs font-medium text-slate-600">Due date</label>
							<input name="due_date" type="date" value="{{ old('due_date', optional($selectedTask->due_date)->format('Y-m-d')) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100" />
						</div>
						<div>
							<label class="mb-1 block text-xs font-medium text-slate-600">Project</label>
							<select name="project_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
								@foreach($projects as $project)
									<option value="{{ $project->id }}" @selected((int) old('project_id', $selectedTask->project_id) === (int) $project->id)>{{ $project->name }}</option>
								@endforeach
							</select>
						</div>
						<div>
							<label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
							<select name="status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
								@foreach(['Todo','Doing','Done'] as $s)
									<option value="{{ $s }}" @selected(old('status', $selectedTask->status) === $s)>{{ $s }}</option>
								@endforeach
							</select>
						</div>
					</div>

					<div>
						<label class="mb-1 block text-xs font-medium text-slate-600">Description</label>
						<div class="richtext" data-richtext data-upload-url="{{ route('uploads.richtext') }}">
							<textarea name="description" class="hidden">{{ old('description', $selectedTask->description) }}</textarea>
							<div
								data-richtext-editor
								data-initial='@json(old('description', $selectedTask->description))'
								class="bg-white"
							></div>
						</div>
					</div>

					<div class="flex items-center justify-end gap-3">
						<button class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Save changes</button>
					</div>
				</form>
			@else
				<div class="mt-4 space-y-4">
					<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
						<div>
							<div class="mb-1 text-xs font-medium text-slate-600">Assignee</div>
							<div class="text-sm text-slate-800">{{ $selectedTask->assignee?->name ?? 'Unassigned' }}</div>
						</div>
						<div>
							<div class="mb-1 text-xs font-medium text-slate-600">Due date</div>
							<div class="text-sm text-slate-800">{{ $selectedTask->due_date ? $selectedTask->due_date->format('Y-m-d') : '—' }}</div>
						</div>
						<div>
							<div class="mb-1 text-xs font-medium text-slate-600">Project</div>
							<div class="text-sm text-slate-800">{{ $selectedTask->project?->name ?? '—' }}</div>
						</div>
						<div>
							<div class="mb-1 text-xs font-medium text-slate-600">Status</div>
							<div class="text-sm text-slate-800">{{ $selectedTask->status }}</div>
						</div>
					</div>

					<div>
						<div class="mb-1 text-xs font-medium text-slate-600">Description</div>
						@if(!empty($selectedTask->description))
							<div class="richtext-content text-sm text-slate-700">{!! $selectedTask->description !!}</div>
						@else
							<div class="text-sm text-slate-500">No description.</div>
						@endif
					</div>
				</div>

				<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
					<div>
						<label class="mb-1 block text-xs font-medium text-slate-600">Replace image (optional)</label>
						<input name="image" type="file" accept="image/*" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
					</div>
					<div data-attachments-picker>
						<label class="mb-1 block text-xs font-medium text-slate-600">Add attachments (optional)</label>
						<div class="mt-2 flex flex-wrap items-center gap-2">
							<input
								name="attachments[]"
								type="file"
								multiple
								class="hidden"
								data-attachments-input
							/>
							<label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-attachments-button>
								<span class="text-lg">+</span>
								Add attachments
							</label>
							<button type="button" class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-attachments-clear>Clear</button>
						</div>
						<div class="mt-1 text-xs text-slate-500" data-attachments-help>You can select multiple files.</div>
						<div class="mt-3 hidden grid grid-cols-2 gap-3 sm:grid-cols-3" data-attachments-preview></div>
					</div>
				</div>
			@endcanany

			@if($selectedTask->image_path)
				<div class="mt-5">
					<div class="text-xs font-medium text-slate-500">Image</div>
					<div class="mt-2 flex items-center gap-3">
						<a href="{{ route('uploads.public', ['path' => $selectedTask->image_path], false) }}" target="_blank" class="inline-flex items-center gap-2">
						<img
							src="{{ route('uploads.public', ['path' => $selectedTask->image_path], false) }}"
							alt="Task image"
							class="h-24 w-24 rounded-xl border border-slate-200 object-cover"
							loading="lazy"
						/>
						<span class="text-sm font-medium text-indigo-700 hover:underline">Open</span>
						</a>
						<a href="{{ route('uploads.public', ['path' => $selectedTask->image_path], false) }}?download=1" class="text-sm font-medium text-slate-700 hover:underline">Download</a>
					</div>
				</div>
			@endif

			@php
				$hasAttachments = ($selectedTask->attachments ?? collect())->count() > 0;
				$canManageAttachments = auth()->user()->can('tasks.manage') || auth()->user()->can('tasks.update') || auth()->user()->can('tasks.attachments.delete');
				$canAddAttachments = auth()->user()->can('tasks.manage') || auth()->user()->can('tasks.update');
			@endphp
			<div class="mt-5">
				<div class="text-xs font-medium text-slate-500">Attachments</div>
				<div class="mt-2 flex items-stretch gap-3 overflow-x-auto pb-1">
					@foreach(($selectedTask->attachments ?? collect()) as $att)
						@include('tasks._attachment_strip_item', ['task' => $selectedTask, 'att' => $att])
					@endforeach
					@if($canAddAttachments)
						<form method="POST" action="{{ route('tasks.attachments.store', $selectedTask) }}" enctype="multipart/form-data" class="shrink-0">
							@csrf
							<input id="task-attachments-strip-upload-{{ $selectedTask->id }}" type="file" name="attachments[]" multiple class="hidden" onchange="this.form.submit()" />
							<label for="task-attachments-strip-upload-{{ $selectedTask->id }}" class="inline-flex h-full w-16 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white text-slate-500 hover:bg-slate-50 cursor-pointer">
								<span class="text-2xl leading-none">+</span>
								<span class="sr-only">Add attachments</span>
							</label>
						</form>
					@endif
				</div>
			</div>

			<div class="mt-6 border-t border-slate-200 pt-5" data-activity-tabs-root>
				@php
					$tab = request('activity', 'comments');
					$tab = in_array($tab, ['comments', 'activity'], true) ? $tab : 'comments';
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
						'at' => $selectedTask->created_at,
						'user' => $selectedTask->creator,
					]);
					foreach (($selectedTask->activities ?? collect()) as $a) {
						$activity = $activity->push([
							'type' => 'change',
							'at' => $a->created_at,
							'user' => $a->user,
							'change_type' => $a->type,
							'meta' => $a->meta ?? [],
						]);
					}
					if ($canViewComments) {
						foreach ($selectedTask->comments as $c) {
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
					$attachmentsById = ($selectedTask->attachments ?? collect())->keyBy('id');
					$commentFeed = collect();
					foreach (($selectedTask->activities ?? collect()) as $a) {
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
						foreach ($selectedTask->comments as $c) {
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
						<button type="button" data-activity-tab="comments" class="-mb-px border-b-2 px-1 pb-2 text-sm font-semibold {{ $tab === 'comments' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}">Comments</button>
						<button type="button" data-activity-tab="activity" class="-mb-px border-b-2 px-1 pb-2 text-sm font-semibold {{ $tab === 'activity' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}">All activity</button>
					</div>
					<div class="text-xs text-slate-400">Oldest</div>
				</div>

				<div class="mt-4" data-activity-panel="comments" style="{{ $tab === 'comments' ? '' : 'display:none' }}">
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
											<div class="text-xs text-slate-500">{{ $at?->format('M j, Y') }}</div>
										@else
											<div class="text-sm text-slate-500">attached</div>
											<div class="text-xs text-slate-500">· {{ $at?->format('M j, Y') }}</div>
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
													@include('tasks._attachment_card', ['task' => $selectedTask, 'att' => $att, 'showDelete' => $canManageAttachments, 'showUploaded' => false])
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
													@include('tasks._attachment_card', ['task' => $selectedTask, 'att' => $att, 'showDelete' => $canManageAttachments, 'showUploaded' => false])
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
						<form method="POST" action="{{ route('tasks.comments.store', $selectedTask) }}" enctype="multipart/form-data" class="mt-5">
							@csrf
							@php
								$notifyName = $selectedTask->assignee?->name;
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

				<div class="mt-4" data-activity-panel="activity" style="{{ $tab === 'activity' ? '' : 'display:none' }}">
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
													<span class="text-slate-500">· {{ $at->format('M j, Y') }}</span>
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
															@include('tasks._attachment_card', ['task' => $selectedTask, 'att' => $att, 'showDelete' => $canManageAttachments, 'showUploaded' => false])
														@endforeach
													</div>
												@endif
											@endif
									@else
										<div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
											<div class="text-sm font-semibold text-slate-900">{{ $user?->name ?? 'Unknown' }}</div>
											<div class="text-xs text-slate-500">{{ $at?->format('M j, Y') }}</div>
										</div>
										@php
											$body = (string) ($evt['body'] ?? '');
											$isHtml = str_contains($body, '<') && str_contains($body, '>');
											$meta = (array) ($evt['meta'] ?? []);
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
													@include('tasks._attachment_card', ['task' => $selectedTask, 'att' => $att, 'showDelete' => $canManageAttachments, 'showUploaded' => false])
												@endforeach
											</div>
										@endif
									@endif
								</div>
							</div>
						@endforeach
					</div>

					@canany(['comments.manage', 'comments.create'])
						<form method="POST" action="{{ route('tasks.comments.store', $selectedTask) }}" enctype="multipart/form-data" class="mt-5">
							@csrf
							@php
								$notifyName = $selectedTask->assignee?->name;
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
			</div>
		</div>
	@else
		<div class="px-5 py-12 text-sm text-slate-500">Select a task to see details here.</div>
	@endif
</div>
