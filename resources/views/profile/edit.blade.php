@extends('layouts.app')

@section('content')
	<div class="max-w-3xl">
		<div class="mb-6">
			<h1 class="text-2xl font-bold tracking-tight">My profile</h1>
			<p class="mt-1 text-sm text-slate-600">Update your name, email, and password.</p>
		</div>

		<div class="space-y-6">
			<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
				<div class="mb-4 text-base font-semibold">Profile details</div>

				<form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
					@csrf
					@method('PATCH')

					<div>
						<label class="mb-1 block text-sm font-medium text-slate-700" for="name">Name</label>
						<input
							id="name"
							name="name"
							type="text"
							value="{{ old('name', $user->name) }}"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100"
							required
						/>
					</div>

					<div>
						<div class="mb-1 block text-sm font-medium text-slate-700">Email</div>
						<div class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
							{{ $user->email }}
						</div>
						<p class="mt-1 text-xs text-slate-500">Email cannot be changed.</p>
					</div>

					<div class="pt-2">
						<button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
							Save profile
						</button>
					</div>
				</form>
			</div>

			<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
				<div class="mb-4 text-base font-semibold">Change password</div>

				<form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
					@csrf
					@method('PATCH')

					<div>
						<label class="mb-1 block text-sm font-medium text-slate-700" for="current_password">Current password</label>
						<input
							id="current_password"
							name="current_password"
							type="password"
							autocomplete="current-password"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100"
							required
						/>
					</div>

					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						<div>
							<label class="mb-1 block text-sm font-medium text-slate-700" for="password">New password</label>
							<input
								id="password"
								name="password"
								type="password"
								autocomplete="new-password"
								class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100"
								required
							/>
						</div>
						<div>
							<label class="mb-1 block text-sm font-medium text-slate-700" for="password_confirmation">Confirm new password</label>
							<input
								id="password_confirmation"
								name="password_confirmation"
								type="password"
								autocomplete="new-password"
								class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100"
								required
							/>
						</div>
					</div>

					<div class="pt-2">
						<button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
							Update password
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
@endsection
