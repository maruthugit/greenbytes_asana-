<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AllowedTaskAttachment implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        $name = (string) $value->getClientOriginalName();
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $mime = strtolower((string) ($value->getMimeType() ?? ''));
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'ai', 'psd'];
        if ($ext === '' || !in_array($ext, $allowedExt, true)) {
            $fail('Attachments must be images, PDF, DOC/DOCX, AI, or PSD.');
            return;
        }

        // Images
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            if (!str_starts_with($mime, 'image/')) {
                $fail('Invalid image attachment.');
            }
            return;
        }

        // PDF
        if ($ext === 'pdf') {
            if ($mime !== 'application/pdf') {
                $fail('Invalid PDF attachment.');
            }
            return;
        }

        // DOC/DOCX
        if ($ext === 'doc') {
            if (!($mime === 'application/msword' || str_contains($mime, 'msword') || str_contains($mime, 'word'))) {
                $fail('Invalid document attachment.');
            }
            return;
        }

        if ($ext === 'docx') {
            if (!($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || str_contains($mime, 'officedocument.wordprocessingml'))) {
                $fail('Invalid document attachment.');
            }
            return;
        }

        // Adobe Illustrator (.ai)
        if ($ext === 'ai') {
            $ok = in_array($mime, [
                'application/postscript',
                'application/illustrator',
                'application/vnd.adobe.illustrator',
                'application/x-illustrator',
                // Some AI files (PDF-compatible) are detected as PDF.
                'application/pdf',
                // Some environments detect AI as generic binary.
                'application/octet-stream',
            ], true) || str_contains($mime, 'illustrator');

            if (!$ok) {
                $fail('Invalid Illustrator attachment.');
            }
            return;
        }

        // Adobe Photoshop (.psd)
        if ($ext === 'psd') {
            $ok = in_array($mime, [
                'image/vnd.adobe.photoshop',
                'application/vnd.adobe.photoshop',
                'application/photoshop',
                'application/x-photoshop',
                // Some environments detect PSD as generic binary.
                'application/octet-stream',
            ], true) || str_contains($mime, 'photoshop');

            if (!$ok) {
                $fail('Invalid Photoshop attachment.');
            }
            return;
        }
    }
}
