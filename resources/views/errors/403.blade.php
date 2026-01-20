@extends('layouts.app')

@section('content')
	@php
		$title = 'No access';
		$message = $exception?->getMessage();
		if (!is_string($message) || trim($message) === '' || $message === 'This action is unauthorized.') {
			$message = "You don't have permission to view this page.";
		}
	@endphp

	<div class="mx-auto max-w-2xl">
		<div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
			<div class="text-xs font-semibold tracking-wide text-slate-500">403</div>
			<h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
			<p class="mt-3 text-sm leading-6 text-slate-600">{{ $message }}</p>

			<div class="mt-6 flex flex-wrap items-center gap-3">
				<a href="{{ url()->previous() }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
					Go back
				</a>
				<a href="/" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
					Home
				</a>
			</div>

			<div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
				If you believe this is a mistake, ask an Admin to grant you the correct module permission (View/Add/Edit/Delete).
			</div>
		</div>
	</div>
@endsection
