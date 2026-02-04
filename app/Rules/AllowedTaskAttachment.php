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

        // Allow any file type; keep only basic upload validity checks.
        if (!$value->isValid()) {
            $fail('Invalid attachment upload.');
        }
    }
}
