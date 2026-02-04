<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TaskCommentComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_empty_comment_with_no_attachments(): void
    {
        $user = User::factory()->create();

        Permission::findOrCreate('comments.create', 'web');
        $user->givePermissionTo('comments.create');

        $team = Team::create(['name' => 'T', 'user_id' => $user->id]);
        $project = Project::create(['team_id' => $team->id, 'name' => 'P']);
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task', 'status' => 'Todo']);

        $response = $this
            ->actingAs($user)
            ->from('/tasks')
            ->post(route('tasks.comments.store', $task), [
                'body' => '',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['body']);
        $this->assertDatabaseCount('task_comments', 0);
    }

    public function test_allows_attachment_only_comment_and_saves_meta_attachment_ids(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Permission::findOrCreate('comments.create', 'web');
        $user->givePermissionTo('comments.create');

        $team = Team::create(['name' => 'T', 'user_id' => $user->id]);
        $project = Project::create(['team_id' => $team->id, 'name' => 'P']);
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task', 'status' => 'Todo']);

        $file = UploadedFile::fake()->create('example.zip', 100, 'application/zip');

        $response = $this
            ->actingAs($user)
            ->from('/tasks')
            ->post(route('tasks.comments.store', $task), [
                'body' => '',
                'attachments' => [$file],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $comment = TaskComment::query()->firstOrFail();
        $this->assertSame($task->id, $comment->task_id);
        $this->assertSame('', (string) $comment->body);

        $meta = (array) ($comment->meta ?? []);
        $this->assertArrayHasKey('attachment_ids', $meta);
        $this->assertCount(1, (array) $meta['attachment_ids']);

        $attachmentId = (int) $meta['attachment_ids'][0];
        $attachment = TaskAttachment::query()->findOrFail($attachmentId);

        $this->assertSame($task->id, $attachment->task_id);
        $this->assertNotSame('', (string) $attachment->path);

        Storage::disk('public')->assertExists($attachment->path);
    }

    public function test_rich_text_comment_body_is_sanitized(): void
    {
        $user = User::factory()->create();

        Permission::findOrCreate('comments.create', 'web');
        $user->givePermissionTo('comments.create');

        $team = Team::create(['name' => 'T', 'user_id' => $user->id]);
        $project = Project::create(['team_id' => $team->id, 'name' => 'P']);
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task', 'status' => 'Todo']);

        $response = $this
            ->actingAs($user)
            ->from('/tasks')
            ->post(route('tasks.comments.store', $task), [
                'body' => '<p><strong>Hi</strong></p><script>alert(1)</script>',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $comment = TaskComment::query()->firstOrFail();
        $this->assertStringNotContainsString('<script', strtolower((string) $comment->body));
        $this->assertStringContainsString('Hi', (string) $comment->body);
    }
}
