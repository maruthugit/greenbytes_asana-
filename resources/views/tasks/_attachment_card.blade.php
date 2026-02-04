@php
	/** @var \App\Models\Task $task */
	/** @var \App\Models\TaskAttachment $att */
	$showDelete = (bool) ($showDelete ?? false);
	$showUploaded = (bool) ($showUploaded ?? true);

	$name = (string) ($att->original_name ?: basename((string) $att->path));
	$path = (string) ($att->path ?? '');
	$mime = strtolower((string) ($att->mime_type ?? ''));
	$ext = strtolower(pathinfo($name !== '' ? $name : $path, PATHINFO_EXTENSION));

	$isImage = str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
	$typeLabel = 'File';
	$badge = 'FILE';
	$badgeClasses = 'bg-slate-100 text-slate-700';
	$iconBg = 'bg-slate-500';
	$iconFg = 'text-white';

	if ($isImage) {
		$typeLabel = 'Image';
		$badge = 'IMG';
		$badgeClasses = 'bg-slate-100 text-slate-700';
		$iconBg = 'bg-slate-500';
		$iconFg = 'text-white';
	} elseif ($ext === 'pdf' || $mime === 'application/pdf') {
		$typeLabel = 'PDF File';
		$badge = 'PDF';
		$badgeClasses = 'bg-rose-100 text-rose-700';
		$iconBg = 'bg-rose-600';
		$iconFg = 'text-white';
	} elseif (in_array($ext, ['doc', 'docx'], true) || str_contains($mime, 'word')) {
		$typeLabel = 'Word Document';
		$badge = 'DOC';
		$badgeClasses = 'bg-sky-100 text-sky-700';
		$iconBg = 'bg-sky-600';
		$iconFg = 'text-white';
	} elseif (in_array($ext, ['xls', 'xlsx'], true) || str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) {
		$typeLabel = 'Spreadsheet';
		$badge = 'XLS';
		$badgeClasses = 'bg-emerald-100 text-emerald-700';
		$iconBg = 'bg-emerald-600';
		$iconFg = 'text-white';
	} elseif (in_array($ext, ['ppt', 'pptx'], true) || str_contains($mime, 'powerpoint') || str_contains($mime, 'presentation')) {
		$typeLabel = 'Presentation';
		$badge = 'PPT';
		$badgeClasses = 'bg-orange-100 text-orange-700';
		$iconBg = 'bg-orange-600';
		$iconFg = 'text-white';
	} elseif ($ext === 'zip' || str_contains($mime, 'zip')) {
		$typeLabel = 'ZIP File';
		$badge = 'ZIP';
		$badgeClasses = 'bg-slate-100 text-slate-700';
		$iconBg = 'bg-slate-600';
		$iconFg = 'text-white';
	}

	$openUrl = route('tasks.attachments.show', [$task, $att]);
	$downloadUrl = route('tasks.attachments.download', [$task, $att]);
	$deleteUrl = route('tasks.attachments.file.destroy', [$task, $att]);
@endphp

<div
	class="group cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50"
	data-attachment-card
	data-open-url="{{ $openUrl }}"
	role="link"
	tabindex="0"
>
	<div class="flex items-center justify-between gap-3">
		<div class="flex min-w-0 flex-1 items-center gap-3">
			<a href="{{ $openUrl }}" target="_blank" class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
				@if($isImage)
					<img src="{{ $openUrl }}" alt="{{ $name }}" width="40" height="40" class="h-full w-full object-cover" loading="lazy" />
				@else
					<div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $iconBg }} {{ $iconFg }}">
						@if($badge === 'ZIP')
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
								<path d="M4 2.75A1.75 1.75 0 0 1 5.75 1h5.19c.464 0 .909.184 1.237.513l3.31 3.31c.329.328.514.773.514 1.237v11.19A1.75 1.75 0 0 1 14.25 19H5.75A1.75 1.75 0 0 1 4 17.25V2.75Zm7 0v3.5h3.5L11 2.75Z" />
								<path d="M9.25 7.5a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5H10a.75.75 0 0 1-.75-.75Zm0 3a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5H10a.75.75 0 0 1-.75-.75Zm0 3a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5H10a.75.75 0 0 1-.75-.75Z" />
							</svg>
						@else
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
								<path d="M4 2.75A1.75 1.75 0 0 1 5.75 1h5.19c.464 0 .909.184 1.237.513l3.31 3.31c.329.328.514.773.514 1.237v11.19A1.75 1.75 0 0 1 14.25 19H5.75A1.75 1.75 0 0 1 4 17.25V2.75Zm7 0v3.5h3.5L11 2.75Z" />
								<path d="M7.5 11.25a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5h-6a.75.75 0 0 1-.75-.75Zm0 3a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5h-6a.75.75 0 0 1-.75-.75Z" />
							</svg>
						@endif
					</div>
				@endif
			</a>
			<div class="min-w-0">
				<a href="{{ $openUrl }}" target="_blank" class="block truncate text-sm font-medium text-slate-900 hover:underline">{{ $name }}</a>
				<div class="mt-0.5 text-xs text-slate-500">
					<span>{{ $typeLabel }}</span>
					<span class="px-1">·</span>
					<a href="{{ $downloadUrl }}" class="font-semibold text-slate-700 hover:underline">Download</a>
				</div>
				@if($showUploaded && $att->created_at)
					<div class="mt-0.5 text-xs text-slate-400">Uploaded {{ $att->created_at->format('d M Y, H:i') }}</div>
				@endif
			</div>
		</div>

		@if($showDelete)
			<details class="relative shrink-0 opacity-0 group-hover:opacity-100 focus-within:opacity-100">
				<summary class="list-none rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 cursor-pointer">
					<span class="sr-only">Attachment actions</span>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
						<path d="M6 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z" />
					</svg>
				</summary>
				<div class="absolute right-0 z-10 mt-2 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
					<form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Delete this attachment?');">
						@csrf
						@method('DELETE')
						<button type="submit" class="block w-full px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50">Delete</button>
					</form>
				</div>
			</details>
		@endif
	</div>
</div>
