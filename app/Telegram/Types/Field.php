<?php

namespace App\Telegram\Types;

class Field
{

    public bool $property = true;

    public readonly \App\Php\Type $type;

    public function __construct(
        public readonly string $name,
        protected string $typeName,
        public readonly string $description,
    ) {

        if (preg_match('#“attach://<file_attach_name>”#', $this->description) === 1 && ! str_contains($this->typeName, 'InputFile')) {
            $this->typeName .= ' or InputFile';
        }

        $this->type = new \App\Php\Type($this->typeName);
    }

    public function optional(): bool
    {
        return preg_match('/<em>Optional\.?<\/em>/', $this->description) === 1;
    }

    public function value(): mixed
    {
        if ($this->typeName === 'True') {
            return true;
        }

        $result = preg_match('/(?|, always [^\w]?(\w+)[^\w]?|must be \<em\>(\w+)\<\/em\>)/u', $this->description, $matches);

        if (! $result) {
            return null;
        }

        return $matches[1];
    }

}
