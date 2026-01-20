@extends('layouts.app')
@section('content')
@php
    $brandLogoCandidates = [
        'images/greenbytes-logo.svg',
        'images/greenbytes-logo.png',
        'images/greenbytes-logo.jpg',
    ];
    $brandLogo = collect($brandLogoCandidates)
        ->first(fn ($path) => file_exists(public_path($path)));
@endphp

<div class="relative">
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 -top-24 h-64 w-64 rounded-full bg-indigo-200/70 blur-3xl"></div>
        <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-sky-200/70 blur-3xl"></div>
        <div class="absolute -bottom-28 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-emerald-100/70 blur-3xl"></div>
    </div>

    <div class="mx-auto max-w-md">
        <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm backdrop-blur sm:p-8">
            <div class="flex items-center gap-4">
                @if ($brandLogo)
                    <img src="{{ asset($brandLogo) }}" alt="{{ config('app.name', 'GreenBytes Asana') }}" class="h-14 w-auto max-w-[160px] object-contain" />
                @else
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-500"></div>
                @endif
                <div>
                    <div class="text-sm font-semibold text-slate-900">{{ config('app.name', 'GreenBytes Asana') }}</div>
                    <div class="text-xs text-slate-500">Work management</div>
                </div>
            </div>

            <h1 class="mt-6 text-2xl font-semibold tracking-tight text-slate-900">Log in</h1>
            <p class="mt-1 text-sm text-slate-600">Welcome back. Please sign in to continue.</p>

            <form method="POST" action="/login" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Email</label>
                    <input
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="{{ old('email') }}"
                        placeholder="you@company.com"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Password</label>
                    <input
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        placeholder="••••••••"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
                >
                    Log in
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-500">
                By signing in you agree to your team’s policies.
            </div>
        </div>

    </div>
</div>
@endsection