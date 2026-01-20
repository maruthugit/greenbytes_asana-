<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RichTextUploadController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth')->group(function () {
	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
	Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

	Route::get('/performance', [PerformanceController::class, 'index'])
			->middleware('role:admin')
		->name('performance');
	Route::get('/performance/export', [PerformanceController::class, 'export'])
			->middleware('role:admin')
		->name('performance.export');
	Route::get('/performance/export.xlsx', [PerformanceController::class, 'exportXlsx'])
			->middleware('role:admin')
		->name('performance.export.xlsx');
	Route::get('/search', [SearchController::class, 'index'])
		->middleware('permission:projects.view|projects.manage|tasks.view|tasks.manage')
		->name('search');
	Route::get('/', [ProjectController::class, 'dashboard'])
		->middleware('permission:projects.view|projects.manage|tasks.view|tasks.manage|teams.view|teams.manage|performance.view');

	Route::prefix('admin')->middleware('permission:users.manage')->group(function () {
		Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
		Route::post('/users', [UserManagementController::class, 'store'])->name('admin.users.store');
		Route::get('/users/{user}', [UserManagementController::class, 'edit'])->name('admin.users.edit');
		Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
	});

	Route::get('/projects/{project}/board', [ProjectController::class, 'board'])
		->middleware('permission:projects.view|projects.manage')
		->name('projects.board');
	Route::patch('/projects/{project}/board', [ProjectController::class, 'updateBoard'])
		->middleware('permission:tasks.manage|tasks.update')
		->name('projects.board.update');

	Route::get('/teams', [TeamController::class, 'index'])
		->middleware('permission:teams.view|teams.manage')
		->name('teams.index');
	Route::post('/teams', [TeamController::class, 'store'])
		->middleware('permission:teams.manage|teams.create')
		->name('teams.store');
	Route::get('/teams/{team}/edit', [TeamController::class, 'edit'])
		->middleware('permission:teams.manage|teams.update')
		->name('teams.edit');
	Route::patch('/teams/{team}', [TeamController::class, 'update'])
		->middleware('permission:teams.manage|teams.update')
		->name('teams.update');
	Route::delete('/teams/{team}', [TeamController::class, 'destroy'])
		->middleware(['permission:teams.manage|teams.delete', 'role:admin'])
		->name('teams.destroy');

	Route::get('/projects', [ProjectController::class, 'index'])
		->middleware('permission:projects.view|projects.manage')
		->name('projects.index');
	Route::post('/projects', [ProjectController::class, 'store'])
		->middleware('permission:projects.manage|projects.create')
		->name('projects.store');
	Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])
		->middleware('permission:projects.manage|projects.update')
		->name('projects.edit');
	Route::patch('/projects/{project}', [ProjectController::class, 'update'])
		->middleware('permission:projects.manage|projects.update')
		->name('projects.update');
	Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
		->middleware(['permission:projects.manage|projects.delete', 'role:admin'])
		->name('projects.destroy');
	Route::get('/tasks', [TaskController::class, 'index'])
		->middleware('permission:tasks.view|tasks.manage')
		->name('tasks.index');
	Route::post('/tasks', [TaskController::class, 'store'])
		->middleware('permission:tasks.manage|tasks.create')
		->name('tasks.store');
	Route::patch('/tasks/{task}', [TaskController::class, 'update'])
		->middleware('permission:tasks.manage|tasks.update')
		->name('tasks.update');
	Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])
		->middleware('permission:tasks.manage|tasks.update|tasks.complete')
		->name('tasks.complete');
	Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
		->middleware(['permission:tasks.manage|tasks.delete', 'role:admin'])
		->name('tasks.destroy');
	Route::delete('/tasks/{task}/attachments', [TaskController::class, 'destroyAttachment'])
		->middleware('permission:tasks.manage|tasks.update|tasks.attachments.delete')
		->name('tasks.attachments.destroy');
	Route::post('/uploads/richtext', [RichTextUploadController::class, 'store'])
		->middleware('permission:tasks.manage|tasks.update')
		->name('uploads.richtext');

	Route::get('/tasks/{task}', [TaskController::class, 'show'])
		->middleware('permission:tasks.view|tasks.manage')
		->name('tasks.show');
	Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])
		->middleware('permission:comments.manage|comments.create')
		->name('tasks.comments.store');
});
