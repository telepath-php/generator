<?php

namespace App\Telegram\Types;

use App\Support\PhpTypeMapper;

class Field
{

    public bool $property = true;

    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $description,
    ) {}

    public function optional(): bool
    {
        return str_starts_with($this->description, 'Optional.');
    }

    public function docType(): string
    {
        return PhpTypeMapper::docType($this->type);
    }

    public function phpType(): string
    {
        return PhpTypeMapper::phpType($this->type);
    }

    public function value(): ?string
    {
        $result = preg_match('/(?|always [^\w]?(\w+)[^\w]?|must be (\w+))/u', $this->description, $matches);

        if (! $result) {
            return null;
        }

        return $matches[1];
    }

}
