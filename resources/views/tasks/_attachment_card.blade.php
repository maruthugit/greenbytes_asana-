@php
	/** @var \App\Models\Task $task */
	/** @var \App\Models\TaskAttachment $att */
	$showDelete = (bool) ($showDelete ?? false);

	$name = (string) ($att->original_name ?: basename((string) $att->path));
	$path = (string) ($att->path ?? '');
	$mime = strtolower((string) ($att->mime_type ?? ''));
	$ext = strtolower(pathinfo($name !== '' ? $name : $path, PATHINFO_EXTENSION));

	$isImage = str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
	$typeLabel = 'Attachment';
	$badge = 'FILE';
	$badgeClasses = 'bg-slate-100 text-slate-700';

	if ($isImage) {
		$typeLabel = 'Image';
		$badge = 'IMG';
		$badgeClasses = 'bg-slate-100 text-slate-700';
	} elseif ($ext === 'pdf' || $mime === 'application/pdf') {
		$typeLabel = 'PDF';
		$badge = 'PDF';
		$badgeClasses = 'bg-rose-100 text-rose-700';
	} elseif (in_array($ext, ['doc', 'docx'], true) || str_contains($mime, 'word')) {
		$typeLabel = 'DOC';
		$badge = 'DOC';
		$badgeClasses = 'bg-sky-100 text-sky-700';
	}

	$openUrl = route('tasks.attachments.show', [$task, $att]);
	$downloadUrl = route('tasks.attachments.download', [$task, $att]);
	$deleteUrl = route('tasks.attachments.file.destroy', [$task, $att]);
@endphp

<div class="group rounded-xl border border-slate-200 bg-white p-3 hover:border-slate-300">
	<div class="flex items-center gap-3">
		<a href="{{ $openUrl }}" target="_blank" class="flex min-w-0 flex-1 items-center gap-3">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
				@if($isImage)
					<img src="{{ $openUrl }}" alt="{{ $name }}" class="h-full w-full object-cover" loading="lazy" />
				@else
					<div class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold {{ $badgeClasses }}">{{ $badge }}</div>
				@endif
			</div>
			<div class="min-w-0">
				<div class="truncate text-sm font-semibold text-slate-900">{{ $name }}</div>
				<div class="mt-0.5 truncate text-xs text-slate-500">{{ $typeLabel }} · <a href="{{ $downloadUrl }}" class="font-semibold text-slate-700 hover:underline">Download</a></div>
			</div>
		</a>

		@if($showDelete)
			<div class="shrink-0">
				<form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Delete this attachment?');">
					@csrf
					@method('DELETE')
					<button class="text-xs font-semibold text-rose-700 hover:underline">Delete</button>
				</form>
			</div>
		@endif
	</div>
</div>
