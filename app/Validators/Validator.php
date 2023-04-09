<?php

namespace App\Validators;

use App\Exceptions\ValidationException;
use App\Telegram\Document;
use Illuminate\Support\Facades\File;

abstract class Validator
{

    abstract public function validate(Document $document): void;

    public function report(mixed ...$vars): void
    {
        $file = storage_path("reports/" . class_basename($this) . '.txt');
        File::ensureDirectoryExists(dirname($file));

        $vars = array_filter($vars);

        $vars = array_map(fn($var) => match (get_debug_type($var)) {
            'array' => var_export($var, true),
            default => $var,
        }, $vars);

        $content = implode("\n\n", $vars);

        file_put_contents($file, $content);
    }

    public function fail(string $message)
    {
        throw new ValidationException("Document validation failed with message: {$message}");
    }

}
