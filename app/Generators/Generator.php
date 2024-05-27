<?php

namespace App\Generators;

use App\Telegram\Document;
use Illuminate\Support\Facades\Process;

abstract class Generator
{
    abstract public function generate(Document $document);

    public function runPint(string $filename): bool
    {
        $result = Process::run(['vendor/bin/pint', $filename]);

        return $result->successful();
    }
}
