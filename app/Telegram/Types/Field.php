<?php

namespace App\Telegram\Types;

use App\Parsers\Parser;

class Field
{
    /**
     * Should this field has its own property on its class
     * @var bool
     */
    public bool $property = true;

    /**
     * Should this field be included in the static make method
     * @var bool
     */
    public bool $staticParameter = true;

    public function __construct(
        public string $name,
        public string $type,
        public string $description,
        public mixed $fixedValue,
        protected string $namespace
    ) {
        if (! is_null($this->fixedValue) && ! $this->optional()) {
            $this->staticParameter = false;
        }
    }

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
