<?php

namespace App\Telegram\Methods;

use App\Parsers\Parser;

class Parameter
{

    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $required,
        public readonly string $description,
        protected ?string $namespace = null,
    ) {
    }

    public function required()
    {
        return $this->required === 'Yes';
    }

    public function phpDocType()
    {
        return Parser::phpDocType($this->type, $this->namespace);
    }

    public function phpType()
    {
        return Parser::phpType($this->type, $this->namespace);
    }

}
