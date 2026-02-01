<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RichTextUploadController extends Controller
{
    private function publicStorageUrl(string $path): string
    {
        $url = Storage::disk('public')->url($path);
        $relative = parse_url($url, PHP_URL_PATH);

        return is_string($relative) && $relative !== '' ? $relative : $url;
    }

    private function publicProxyUrl(string $path): string
    {
        $path = ltrim($path, '/');

        // Generate a relative URL so scheme/host mismatches (http vs https) cannot break rendering.
        return route('uploads.public', ['path' => $path], false);
    }

    private function isAllowedPublicUploadPath(string $path): bool
    {
        return str_starts_with($path, 'task-images/')
            || str_starts_with($path, 'task-description/')
            || str_starts_with($path, 'task-attachments/');
    }

    public function show(Request $request, string $path)
    {
        $path = ltrim($path, '/');

        if ($path === '' || !$this->isAllowedPublicUploadPath($path)) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        if ($request->boolean('download')) {
            $name = $request->query('name');
            $downloadName = is_string($name) && $name !== '' ? basename($name) : basename($path);

            return Storage::disk('public')->download($path, $downloadName);
        }

        return Storage::disk('public')->response($path);
    }

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
                'url' => $this->publicProxyUrl($path),
            ]);
        }

        $path = $file->store('task-attachments', 'public');

        return response()->json([
            'type' => 'file',
            'url' => $this->publicProxyUrl($path),
            'name' => $file->getClientOriginalName(),
        ]);
    }
}
