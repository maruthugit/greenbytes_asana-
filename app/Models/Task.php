<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaskComment;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'title', 'description', 'status', 'due_date', 'assigned_to', 'created_by', 'position', 'image_path'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderByDesc('id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function activities()
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }
}
