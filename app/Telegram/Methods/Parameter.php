<?php

namespace App\Telegram\Methods;

use App\Php\Type;

readonly class Parameter
{

    public Type $type;

    public function __construct(
        public string $name,
        string $typeName,
        public string $required,
        public string $description,
    ) {
        $this->type = new Type($typeName);
    }

    public function optional(): bool
    {
        return $this->required === 'Optional';
    }


}
