<?php

namespace App\Telegram\Methods;

readonly class Parameter
{

    public function __construct(
        public string $name,
        public string $type,
        public string $required,
        public string $description,
    ) {}

    public function optional(): bool
    {
        return $this->required === 'Optional';
    }


}
