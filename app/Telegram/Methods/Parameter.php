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

        if ($this->name === 'parse_mode') {
            $this->type->prependType(config('generator.type.parse_mode_enum'));

            dump($this->type->docType);
            dump($this->type->phpType);
        }
    }

    public function optional(): bool
    {
        return $this->required === 'Optional';
    }
}
