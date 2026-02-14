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
				$unreadCommentNotificationsCount = 0;
				$unreadCommentTaskSummaries = collect();
				$unreadAssignmentNotifications = collect();
				$unreadNotificationsCount = 0;
				try {
					$commentType = \App\Notifications\TaskCommentAddedNotification::class;
					$assignedType = \App\Notifications\TaskAssignedNotification::class;

					$unreadAll = auth()->user()
						?->unreadNotifications()
						->whereIn('type', [$commentType, $assignedType])
						->orderByDesc('created_at')
						->limit(300)
						->get() ?? collect();

					$unreadNotificationsCount = (int) $unreadAll->count();
					$unreadComments = $unreadAll->where('type', $commentType)->values();
					$unreadAssignments = $unreadAll->where('type', $assignedType)->values();

					$unreadCommentNotificationsCount = (int) $unreadComments->count();
					$unreadAssignmentNotifications = $unreadAssignments->take(10);

					$grouped = $unreadComments
						->groupBy(function ($n) {
							try {
								$data = is_array($n->data) ? $n->data : (array) $n->data;
								return (int) ($data['task_id'] ?? 0);
							} catch (\Throwable $e) {
								return 0;
							}
						})
						->filter(fn ($items, $taskId) => (int) $taskId > 0);

					$unreadCommentTaskSummaries = $grouped
						->map(function ($items, $taskId) {
							$latest = $items->first();
							$data = is_array($latest->data) ? $latest->data : (array) $latest->data;
							return [
								'task_id' => (int) $taskId,
								'task_title' => (string) ($data['task_title'] ?? 'Task'),
								'commenter' => (string) ($data['commenter'] ?? 'Someone'),
								'excerpt' => (string) ($data['excerpt'] ?? ''),
								'count' => (int) $items->count(),
								'created_at' => $latest->created_at,
							];
						})
						->sortByDesc(fn ($row) => $row['created_at'] ?? null)
						->values()
						->take(10);
				} catch (\Throwable $e) {
					$unreadCommentNotificationsCount = 0;
					$unreadCommentTaskSummaries = collect();
					$unreadAssignmentNotifications = collect();
					$unreadNotificationsCount = 0;
				}
			@endphp
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
							<button
								id="gb-mobile-nav-open"
								type="button"
								class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-800 hover:bg-slate-50 lg:hidden"
								aria-label="Open menu"
							>
								<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M4 7h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									<path d="M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									<path d="M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
								</svg>
							</button>
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
								<div class="relative">
									<button
										id="gb-notif-btn"
										type="button"
										aria-label="Notifications"
										aria-expanded="false"
										class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-800 hover:bg-slate-50"
									>
										<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2Z" fill="currentColor" opacity=".85"/>
											<path d="M18 16v-5a6 6 0 1 0-12 0v5l-2 2h16l-2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
											<path d="M9 4a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
										</svg>
										@if (($unreadNotificationsCount ?? 0) > 0)
											<span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold leading-none text-white ring-2 ring-white" style="position:absolute; top:-4px; right:-4px; height:20px; min-width:20px; padding:0 4px; background:#dc2626; color:#fff; border-radius:9999px; border:2px solid #fff; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; line-height:1;">
												{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
											</span>
										@endif
									</button>

									<div
										id="gb-notif-panel"
										class="hidden absolute right-0 z-50 mt-2 w-[360px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"
										role="dialog"
										aria-label="Notifications"
									>
										<div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
											<div class="text-sm font-semibold text-slate-900">
												Notifications
												@if (($unreadNotificationsCount ?? 0) > 0)
													<span class="ml-1 text-slate-500">({{ $unreadNotificationsCount }})</span>
												@endif
											</div>
											<div class="text-xs font-medium text-slate-500">All</div>
										</div>

										<div class="max-h-[420px] overflow-auto">
											@if (($unreadNotificationsCount ?? 0) === 0)
												<div class="px-4 py-6 text-sm text-slate-600">No new notifications.</div>
											@else
												@foreach ($unreadAssignmentNotifications as $n)
													@php
														$data = is_array($n->data) ? $n->data : (array) $n->data;
														$taskId = (int) ($data['task_id'] ?? 0);
														$title = (string) ($data['task_title'] ?? 'Task');
														$assigner = (string) ($data['assigner'] ?? 'Someone');
												@endphp
												<a href="{{ $taskId ? url('/tasks/' . $taskId) : '#' }}" class="block border-b border-slate-100 px-4 py-3 hover:bg-slate-50">
													<div class="flex items-start gap-3">
														<div class="mt-1 h-2.5 w-2.5 rounded-full bg-slate-900/30"></div>
														<div class="min-w-0 flex-1">
															<div class="truncate text-sm font-semibold text-slate-900">Task assigned: {{ $title }}</div>
															<div class="mt-0.5 truncate text-sm text-slate-700">Assigned by {{ $assigner }}</div>
															<div class="mt-1 text-xs text-slate-500">{{ optional($n->created_at)->diffForHumans() }}</div>
														</div>
													</div>
												</a>
												@endforeach

												@foreach ($unreadCommentTaskSummaries as $row)
													@php
														$taskId = (int) ($row['task_id'] ?? 0);
														$title = (string) ($row['task_title'] ?? 'Task');
														$commenter = (string) ($row['commenter'] ?? 'Someone');
														$excerpt = (string) ($row['excerpt'] ?? '');
														$count = (int) ($row['count'] ?? 0);
														$createdAt = $row['created_at'] ?? null;
													@endphp
													<a href="{{ $taskId ? url('/tasks/' . $taskId) : '#' }}" class="block border-b border-slate-100 px-4 py-3 hover:bg-slate-50">
													<div class="flex items-start gap-3">
														<div class="mt-1 h-2.5 w-2.5 rounded-full bg-slate-900/30"></div>
														<div class="min-w-0 flex-1">
															<div class="flex items-start justify-between gap-3">
																<div class="min-w-0">
																	<div class="truncate text-sm font-semibold text-slate-900">{{ $title }}</div>
																	<div class="mt-0.5 truncate text-sm text-slate-700">{{ $commenter }}: {{ $excerpt }}</div>
																</div>
																@if ($count > 0)
																		<span class="shrink-0 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold leading-none text-white ring-2 ring-white" style="height:20px; min-width:20px; padding:0 4px; background:#dc2626; color:#fff; border-radius:9999px; border:2px solid #fff; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; line-height:1;">
																		{{ $count > 99 ? '99+' : $count }}
																	</span>
																@endif
															</div>
															<div class="mt-1 text-xs text-slate-500">{{ optional($createdAt)->diffForHumans() }}</div>
														</div>
													</div>
													</a>
												@endforeach
											@endif
										</div>
									</div>
								</div>
								<div class="hidden text-sm font-medium text-slate-700 sm:block">{{ auth()->user()->name }}</div>
								<a href="{{ route('profile.edit') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
									Profile
								</a>
								<form method="POST" action="/logout">
									@csrf
									<button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
										Logout
									</button>
								</form>
							</div>
						</div>
					</header>

					<script>
						(function () {
							const btn = document.getElementById('gb-notif-btn');
							const panel = document.getElementById('gb-notif-panel');
							if (!btn || !panel) return;

							function close() {
								panel.classList.add('hidden');
								btn.setAttribute('aria-expanded', 'false');
							}

							function toggle() {
								panel.classList.toggle('hidden');
								btn.setAttribute('aria-expanded', panel.classList.contains('hidden') ? 'false' : 'true');
							}

							btn.addEventListener('click', (e) => {
								e.preventDefault();
								e.stopPropagation();
								toggle();
							});

							document.addEventListener('click', (e) => {
								if (panel.classList.contains('hidden')) return;
								if (panel.contains(e.target) || btn.contains(e.target)) return;
								close();
							});

							document.addEventListener('keydown', (e) => {
								if (e.key === 'Escape') close();
							});
						})();
					</script>

					<!-- Mobile menu overlay (small screens only) -->
					<div id="gb-mobile-nav" style="display:none; position: fixed; inset: 0; z-index: 60;">
						<div id="gb-mobile-nav-backdrop" style="position:absolute; inset:0; background: rgba(15,23,42,.55);"></div>
						<div role="dialog" aria-modal="true" aria-label="Menu" style="position:absolute; top: 0; left: 0; height: 100%; width: 320px; max-width: calc(100vw - 56px); background: #0f172a; color: #f8fafc; box-shadow: 0 25px 50px -12px rgba(0,0,0,.45);">
							<div style="display:flex; align-items:center; justify-content: space-between; padding: 14px 14px 10px; border-bottom: 1px solid rgba(255,255,255,.08);">
								<div style="font-weight: 700; font-size: 14px; letter-spacing: .01em;">Menu</div>
								<button id="gb-mobile-nav-close" type="button" aria-label="Close menu" style="appearance:none; border:0; background: rgba(255,255,255,.08); color:#fff; border-radius: 12px; padding: 8px 10px; cursor:pointer;">
									✕
								</button>
							</div>
							<div style="padding: 12px 10px;">
								@foreach ($nav as $item)
									@php
										$active = $item['match']();
									@endphp
									<a href="{{ $item['href'] }}" style="display:flex; align-items:center; gap: 10px; padding: 10px 12px; border-radius: 14px; margin-bottom: 6px; color: rgba(248,250,252,.92); text-decoration:none; background: {{ $active ? 'rgba(255,255,255,.12)' : 'transparent' }};">
										<span style="display:inline-flex; height: 34px; width: 34px; align-items:center; justify-content:center; border-radius: 12px; background: rgba(255,255,255,.08);">
											<svg viewBox="0 0 24 24" style="height: 16px; width: 16px;" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M4 12a8 8 0 1 1 16 0a8 8 0 0 1-16 0Z" stroke="currentColor" stroke-width="2" opacity=".25"/>
												<path d="M8 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
											</svg>
										</span>
										<span style="font-size: 14px; font-weight: 600;">{{ $item['label'] }}</span>
									</a>
								@endforeach

								@can('users.manage')
									<a href="{{ route('admin.users.index') }}" style="display:flex; align-items:center; gap: 10px; padding: 10px 12px; border-radius: 14px; margin-top: 8px; color: rgba(248,250,252,.92); text-decoration:none; background: {{ request()->is('admin/*') ? 'rgba(255,255,255,.12)' : 'transparent' }};">
										<span style="display:inline-flex; height: 34px; width: 34px; align-items:center; justify-content:center; border-radius: 12px; background: rgba(255,255,255,.08);">
											<svg viewBox="0 0 24 24" style="height: 16px; width: 16px;" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M12 2a5 5 0 0 0-5 5v1a5 5 0 1 0 10 0V7a5 5 0 0 0-5-5Z" stroke="currentColor" stroke-width="2"/>
												<path d="M4 22a8 8 0 1 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
										</svg>
										</span>
										<span style="font-size: 14px; font-weight: 600;">Admin</span>
									</a>
								@endcan

								<div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,.08); display:flex; gap: 10px;">
									<a href="{{ route('profile.edit') }}" style="flex:1; text-align:center; padding: 10px 12px; border-radius: 14px; background: rgba(255,255,255,.10); color: rgba(248,250,252,.95); text-decoration:none; font-weight: 700; font-size: 13px;">Profile</a>
									<form method="POST" action="/logout" style="flex:1;">
										@csrf
										<button type="submit" style="width:100%; padding: 10px 12px; border-radius: 14px; border: 0; cursor:pointer; background: rgba(255,255,255,.16); color: rgba(248,250,252,.98); font-weight: 700; font-size: 13px;">Logout</button>
									</form>
								</div>
							</div>
						</div>
					</div>

					<script>
						(function () {
							const openBtn = document.getElementById('gb-mobile-nav-open');
							const overlay = document.getElementById('gb-mobile-nav');
							const closeBtn = document.getElementById('gb-mobile-nav-close');
							const backdrop = document.getElementById('gb-mobile-nav-backdrop');
							if (!openBtn || !overlay) return;
							const open = () => {
								overlay.style.display = 'block';
								document.body.style.overflow = 'hidden';
							};
							const close = () => {
								overlay.style.display = 'none';
								document.body.style.overflow = '';
							};
							openBtn.addEventListener('click', open);
							closeBtn && closeBtn.addEventListener('click', close);
							backdrop && backdrop.addEventListener('click', close);
							document.addEventListener('keydown', (e) => {
								if (e.key === 'Escape') close();
							});
						})();
					</script>

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
