@php
	/** @var \App\Models\Task $task */
	/** @var \App\Models\TaskAttachment $att */

	$name = (string) ($att->original_name ?: basename((string) $att->path));
	$path = (string) ($att->path ?? '');
	$mime = strtolower((string) ($att->mime_type ?? ''));
	$ext = strtolower(pathinfo($name !== '' ? $name : $path, PATHINFO_EXTENSION));

	$isImage = str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
	$typeLabel = 'File';

	$iconBg = 'bg-slate-500';
	$iconFg = 'text-white';

	if ($isImage) {
		$typeLabel = 'Image';
		$iconBg = 'bg-slate-500';
		$iconFg = 'text-white';
	} elseif ($ext === 'pdf' || $mime === 'application/pdf') {
		$typeLabel = 'PDF File';
		$iconBg = 'bg-rose-600';
		$iconFg = 'text-white';
	} elseif (in_array($ext, ['doc', 'docx'], true) || str_contains($mime, 'word')) {
		$typeLabel = 'Word Document';
		$iconBg = 'bg-sky-600';
		$iconFg = 'text-white';
	} elseif (in_array($ext, ['xls', 'xlsx'], true) || str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) {
		$typeLabel = 'Spreadsheet';
		$iconBg = 'bg-emerald-600';
		$iconFg = 'text-white';
	} elseif (in_array($ext, ['ppt', 'pptx'], true) || str_contains($mime, 'powerpoint') || str_contains($mime, 'presentation')) {
		$typeLabel = 'Presentation';
		$iconBg = 'bg-orange-600';
		$iconFg = 'text-white';
	} elseif ($ext === 'zip' || str_contains($mime, 'zip')) {
		$typeLabel = 'ZIP File';
		$iconBg = 'bg-slate-600';
		$iconFg = 'text-white';
	}

	$openUrl = route('tasks.attachments.show', [$task, $att]);
@endphp

<a
	href="{{ $openUrl }}"
	target="_blank"
	class="group inline-flex min-w-[240px] max-w-[320px] items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-slate-300 hover:bg-slate-50 hover:shadow-sm focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-100"
>
	<div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg">
		@if($isImage)
			<img src="{{ $openUrl }}" alt="{{ $name }}" width="40" height="40" class="h-10 w-10 rounded-lg border border-slate-200 object-cover" loading="lazy" />
		@else
			<div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $iconBg }} {{ $iconFg }}">
				@if($typeLabel === 'ZIP File')
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
	</div>
	<div class="min-w-0">
		<div class="truncate text-sm font-medium text-slate-900">{{ $name }}</div>
		<div class="mt-0.5 truncate text-xs text-slate-500">{{ $typeLabel }}</div>
	</div>
</a>
