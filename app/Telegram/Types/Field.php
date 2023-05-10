<?php

namespace App\Telegram\Types;

class Field
{

    public bool $property = true;

    public readonly \App\Php\Type $type;

    public function __construct(
        public readonly string $name,
        string $typeName,
        public readonly string $description,
    ) {
        $this->type = new \App\Php\Type($typeName);
    }

    public function optional(): bool
    {
        return str_starts_with($this->description, 'Optional.');
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
