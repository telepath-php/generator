<?php

namespace App\Telegram\Types;

use App\Parsers\Parser;

class Field
{
    public function __construct(
        public string $name,
        public string $type,
        public string $description,
        public ?string $fixedValue,
        protected string $namespace
    ) {}

    public function optional(): bool
    {
        return str_starts_with($this->description, 'Optional');
    }

    public function phpDocType(): ?string
    {
        return Parser::phpDocType($this->type, $this->namespace);
    }

    public function phpType(): string
    {
        return Parser::phpType($this->type, $this->namespace);
    }

}
