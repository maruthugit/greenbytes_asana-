@extends('layouts.app')

@section('content')
	@php
		$title = 'PAGE EXPIRED';
		$message = 'Your session has expired or this page timed out. Please log in again and try once more.';
		$loginUrl = Route::has('login') ? route('login') : url('/login');
	@endphp

	<div class="mx-auto flex min-h-[60vh] max-w-3xl items-center justify-center px-4">
		<div class="w-full">
			<div class="flex flex-col items-center text-center">
				<div class="flex items-center gap-4">
					<div class="text-2xl font-semibold tracking-tight text-slate-900">419</div>
					<div class="h-7 w-px bg-slate-200"></div>
					<div class="text-sm font-semibold tracking-[0.22em] text-slate-500">{{ $title }}</div>
				</div>

				<p class="mt-5 max-w-xl text-sm leading-6 text-slate-600">{{ $message }}</p>
				<p class="mt-3 text-sm font-semibold text-slate-800">Please click Login to continue.</p>

				<div class="mt-6 flex flex-wrap items-center justify-center gap-3">
					<a href="{{ $loginUrl }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
						Login
					</a>
					<a href="/" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
						Home
					</a>
					<a href="{{ url()->current() }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
						Refresh
					</a>
				</div>

				<div class="mt-6 max-w-xl rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600">
					If you were filling a form, open it again and resubmit.
				</div>
			</div>
		</div>
	</div>
@endsection
