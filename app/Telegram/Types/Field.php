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
        } elseif ($this->name === 'parse_mode') {
            $this->typeName = config('generator.type.parse_mode_enum').' or '.$this->typeName;
        }

        $this->type = new \App\Php\Type($this->typeName);
    }

    public function optional(): bool
    {
        return preg_match('/<em>Optional\.?<\/em>/', $this->description) === 1;
    }

    public function valueFromDescription(): mixed
    {
        /*
         * Examples:
         * always “creator”
         * Always 0
         * must be <em>all_private_chats</em>
         */
        $result = preg_match('/(?|always “(\w+)”|always (\d+)|must be \<em\>(\w+)\<\/em\>)/ui', $this->description, $matches);

        if (! $result) {
            return null;
        }

        $value = $matches[1];

        if (is_numeric($value)) {
            $value = (int) $value;
        }

        return $value;
    }

    public function value(): mixed
    {
        if ($this->typeName === 'True') {
            return true;
        }

        return $this->valueFromDescription();
    }
}
