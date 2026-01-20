@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900">Create account</h1>
        <p class="mt-1 text-sm text-slate-500">Start organizing work.</p>

        <form method="POST" action="/register" class="mt-6 space-y-3">
            @csrf
            <input name="name" value="{{ old('name') }}" placeholder="Name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
            <input name="email" value="{{ old('email') }}" placeholder="Email" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
            <input name="password" type="password" placeholder="Password (min 8 chars)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
            <button class="w-full rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Register</button>
        </form>

        <div class="mt-4 text-sm text-slate-600">
            Already have an account?
            <a href="/login" class="font-semibold text-indigo-700 hover:underline">Log in</a>
        </div>
    </div>
</div>
@endsection