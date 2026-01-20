<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RichTextUploadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:8192'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'];

        if ($file->isValid() && str_starts_with((string) $file->getMimeType(), 'image/')) {
            $path = $file->store('task-description', 'public');

            return response()->json([
                'type' => 'image',
                'url' => Storage::url($path),
            ]);
        }

        $path = $file->store('task-attachments', 'public');

        return response()->json([
            'type' => 'file',
            'url' => Storage::url($path),
            'name' => $file->getClientOriginalName(),
        ]);
    }
}
