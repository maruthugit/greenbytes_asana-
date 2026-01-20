<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>{{ config('app.name', 'GreenBytes Asana') }}</title>

	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

	@php
		$hasViteAssets = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
		$brandLogoCandidates = [
			'images/greenbytes-logo.svg',
			'images/greenbytes-logo.png',
			'images/greenbytes-logo.jpg',
		];
		$brandLogo = collect($brandLogoCandidates)
			->first(fn ($path) => file_exists(public_path($path)));
	@endphp

	@if (app()->environment('testing'))
		<style>
			/* Keep feature tests independent from Vite builds */
			body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
		</style>
	@elseif ($hasViteAssets)
		@vite(['resources/css/app.css', 'resources/js/app.js'])
	@else
		<style>
			/* Minimal fallback if Vite isn't running/built yet */
			body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
		</style>
	@endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
	<div class="min-h-screen">
		@auth
			@php
				$nav = [
					[
						'label' => 'Home',
						'href' => '/',
						'active' => request()->is('/'),
						'match' => fn () => request()->path() === '/',
						'permissions' => ['projects.view', 'projects.manage', 'tasks.view', 'tasks.manage', 'teams.view', 'teams.manage', 'performance.view'],
					],
					[
						'label' => 'My tasks',
						'href' => '/tasks',
						'match' => fn () => request()->is('tasks*'),
						'permissions' => ['tasks.view', 'tasks.manage'],
					],
					[
						'label' => 'Projects',
						'href' => '/projects',
						'match' => fn () => request()->is('projects*'),
						'permissions' => ['projects.view', 'projects.manage'],
					],
					[
						'label' => 'Teams',
						'href' => '/teams',
						'match' => fn () => request()->is('teams*'),
						'permissions' => ['teams.view', 'teams.manage'],
					],
					[
						'label' => 'Performance',
						'href' => '/performance',
						'match' => fn () => request()->is('performance*'),
						'roles' => ['admin'],
					],
				];

				$nav = array_values(array_filter($nav, function ($item) {
					$roles = $item['roles'] ?? [];
					if (!empty($roles)) {
						foreach ($roles as $role) {
							if (auth()->user()->hasRole($role)) {
								return true;
							}
						}
						return false;
					}

					$permissions = $item['permissions'] ?? [];
					if (empty($permissions)) {
						return true;
					}
					foreach ($permissions as $permission) {
						if (auth()->user()->can($permission)) {
							return true;
						}
					}
					return false;
				}));
			@endphp

			<div class="flex min-h-screen">
				<aside class="hidden w-72 shrink-0 bg-slate-900 text-slate-100 lg:flex lg:flex-col">
					<div class="flex items-center gap-3 px-5 py-5">
						@if ($brandLogo)
							<img src="{{ asset($brandLogo) }}" alt="{{ config('app.name', 'GreenBytes Asana') }}" class="h-10 w-auto max-w-[160px] object-contain" />
						@else
							<div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-sky-500"></div>
						@endif
						<div class="leading-tight">
							<div class="text-sm font-semibold">{{ config('app.name', 'GreenBytes Asana') }}</div>
							<div class="text-xs text-slate-300/80">Work management</div>
						</div>
					</div>

					<nav class="px-3 pb-4">
						@foreach ($nav as $item)
								@php
									$active = $item['match']();
								@endphp
							<a
								href="{{ $item['href'] }}"
								class="group mb-1 flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-white/10 text-white' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}"
							>
								<span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/5 group-hover:bg-white/10">
									<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M4 12a8 8 0 1 1 16 0a8 8 0 0 1-16 0Z" stroke="currentColor" stroke-width="2" opacity=".25"/>
										<path d="M8 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
								</span>
								<span class="truncate">{{ $item['label'] }}</span>
							</a>
						@endforeach

						@can('users.manage')
							<a
								href="{{ route('admin.users.index') }}"
								class="group mb-1 mt-2 flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->is('admin/*') ? 'bg-white/10 text-white' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}"
							>
								<span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/5 group-hover:bg-white/10">
									<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M12 2a5 5 0 0 0-5 5v1a5 5 0 1 0 10 0V7a5 5 0 0 0-5-5Z" stroke="currentColor" stroke-width="2"/>
										<path d="M4 22a8 8 0 1 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
								</span>
								<span class="truncate">Admin</span>
							</a>
						@endcan
					</nav>

					<div class="mt-auto px-5 pb-5 text-xs text-slate-300/70">
						Asana-inspired UI (not affiliated)
					</div>
				</aside>

				<div class="min-w-0 flex-1">
					<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
						<div class="mx-auto flex h-14 max-w-7xl items-center gap-3 px-4">
							<div class="flex-1">
								@canany(['projects.view', 'projects.manage', 'tasks.view', 'tasks.manage'])
									<form method="GET" action="/search" class="max-w-2xl">
										<label class="sr-only" for="q">Search</label>
										<div class="relative">
											<svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
												<path d="M10.5 18a7.5 7.5 0 1 1 0-15a7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>
										</svg>
										<input
											id="q"
											name="q"
											value="{{ request('q') }}"
											placeholder="Search"
											class="w-full rounded-2xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100"
										/>
									</div>
								</form>
								@endcanany
							</div>

							<div class="flex items-center gap-3">
								<div class="hidden text-sm font-medium text-slate-700 sm:block">{{ auth()->user()->name }}</div>
								<form method="POST" action="/logout">
									@csrf
									<button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
										Logout
									</button>
								</form>
							</div>
						</div>
					</header>

					<main class="mx-auto max-w-7xl px-4 py-6">
						@php
							$toast = session('toast');
							if (is_string($toast) && $toast !== '') {
								$toast = ['type' => 'success', 'message' => $toast];
							}
							$type = is_array($toast) ? ($toast['type'] ?? 'success') : null;
							$message = is_array($toast) ? ($toast['message'] ?? null) : null;
							$type = in_array($type, ['success', 'error', 'info'], true) ? $type : 'success';
						@endphp
						@if (!empty($message))
							<div id="gb-toast" style="position: fixed; left: 16px; bottom: 16px; z-index: 9999; pointer-events: none;">
								<style>
									@media (min-width: 1024px) {
										/* Place toast inside the left sidebar bottom area */
										#gb-toast { left: 16px !important; bottom: 72px !important; }
									}
								</style>
								@php
									$palette = [
										'success' => ['title' => 'Success', 'bg' => '#ECFDF5', 'border' => '#A7F3D0', 'text' => '#064E3B'],
										'error' => ['title' => 'Error', 'bg' => '#FFF1F2', 'border' => '#FECDD3', 'text' => '#881337'],
										'info' => ['title' => 'Info', 'bg' => '#FFFFFF', 'border' => '#E2E8F0', 'text' => '#0F172A'],
									];
									$p = $palette[$type] ?? $palette['success'];
								@endphp
								<div role="status" aria-live="polite" style="width: 360px; max-width: calc(100vw - 32px); border-radius: 16px; border: 1px solid {{ $p['border'] }}; background: {{ $p['bg'] }}; color: {{ $p['text'] }}; padding: 12px 14px; box-shadow: 0 10px 15px -3px rgba(0,0,0,.12), 0 4px 6px -4px rgba(0,0,0,.12); pointer-events: auto; font-size: 14px; line-height: 1.35;">
									<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
										<div style="font-weight: 600;">{{ $p['title'] }}</div>
										<button type="button" aria-label="Close" onclick="document.getElementById('gb-toast')?.remove()" style="appearance: none; border: 0; background: rgba(255,255,255,.6); color: rgba(15,23,42,.7); border-radius: 10px; padding: 4px 8px; line-height: 1; cursor: pointer;">
											✕
										</button>
									</div>
									<div style="margin-top: 6px; opacity: .95;">{{ $message }}</div>
								</div>
							</div>
							<script>
								setTimeout(() => {
									const el = document.getElementById('gb-toast');
									if (!el) return;
									el.style.transition = 'opacity 220ms ease';
									el.style.opacity = '0';
									setTimeout(() => el.remove(), 260);
								}, 3000);
							</script>
						@endif

						@if ($errors->any())
							<div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
								<div class="font-semibold">Please fix the following:</div>
								<ul class="mt-2 list-disc pl-5">
									@foreach ($errors->all() as $error)
										<li>{{ $error }}</li>
									@endforeach
								</ul>
							</div>
						@endif

						@yield('content')
					</main>
				</div>
			</div>
		@else
			<div class="mx-auto max-w-7xl px-4 py-10">
				@if ($errors->any())
					<div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
						<div class="font-semibold">Please fix the following:</div>
						<ul class="mt-2 list-disc pl-5">
							@foreach ($errors->all() as $error)
								<li>{{ $error }}</li>
							@endforeach
						</ul>
					</div>
				@endif

				@yield('content')
			</div>
		@endauth
	</div>
</body>
</html>
