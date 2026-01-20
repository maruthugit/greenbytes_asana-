@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold text-slate-900">{{ $project->name }}</h1>
        <p class="text-sm text-slate-500">Board view</p>
    </div>

    <div class="flex items-center gap-2">
        <a href="/projects" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Projects</a>
        <a href="/tasks" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">All Tasks</a>
    </div>
</div>

<div
    data-kanban-board
    data-update-url="{{ route('projects.board.update', $project) }}"
    class="grid grid-cols-1 gap-4 lg:grid-cols-3"
>
    @foreach($columns as $status => $tasks)
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="h-2.5 w-2.5 rounded-full {{ $status === 'Todo' ? 'bg-slate-400' : ($status === 'Doing' ? 'bg-amber-400' : 'bg-emerald-500') }}"></div>
                    <div class="text-sm font-semibold text-slate-800">{{ $status }}</div>
                    <div class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $tasks->count() }}</div>
                </div>
            </div>

            <div data-column-status="{{ $status }}" class="min-h-[240px] space-y-2 p-3">
                @foreach($tasks as $task)
                    <article
                        draggable="true"
                        data-task-id="{{ $task->id }}"
                        data-open-url="{{ url('/tasks') . '?' . http_build_query(['task' => $task->id]) }}"
                        tabindex="0"
                        role="link"
                        aria-label="Open task {{ $task->title }}"
                        class="cursor-pointer rounded-xl border border-slate-200 bg-white p-3 shadow-sm hover:border-slate-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</div>
                                @if($task->image_path)
                                    <div class="mt-2">
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($task->image_path) }}"
                                            alt="Task image"
                                            class="h-20 w-full rounded-lg border border-slate-200 object-cover"
                                            loading="lazy"
                                    />
                                </div>
                                @endif
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    @if($task->assignee)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $task->assignee->name }}</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5">Unassigned</span>
                                    @endif
                                    @if($task->due_date)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5">Due {{ $task->due_date->format('Y-m-d') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-xs text-slate-400">#{{ $task->id }}</div>
                        </div>
                    </article>
                @endforeach

                @if($tasks->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-500">
                        Drop tasks here
                    </div>
                @endif
            </div>
        </section>
    @endforeach
</div>
@endsection
