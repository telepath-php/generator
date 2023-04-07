<?php

namespace App\Telegram\Types;

readonly class Field
{

    public function __construct(
        public string $name,
        public string $type,
        public string $description,
    ) {}

    public function optional(): bool
    {
        return str_starts_with($this->description, 'Optional.');
    }

}
