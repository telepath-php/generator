<?php

namespace App\Telegram;

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

    public function phpDocType(string $type = null)
    {
        $type ??= $this->type;

        if (str_starts_with($type, 'Array of')) {
            $subType = substr($type, 9);
            $arrayType = $this->phpDocType($subType);
            return str_contains($arrayType, '|')
                ? str_replace('|', '[]|', $arrayType) . '[]'
                : $arrayType . '[]';
        }

        $parts = str($type)->split('/(?: or |, | and )/', 2);
        if (count($parts) > 1) {
            return $this->phpDocType($parts[0]) . '|' . $this->phpDocType($parts[1]);
        }

        $type = match ($type) {
            'String'                   => 'string',
            'Integer'                  => 'int',
            'Float', 'Float number'    => 'float',
            'Boolean', 'True', 'False' => 'bool',
            default                    => $this->namespace . $type
        };

        return $type;
    }

    public function phpType()
    {
        $type = $this->phpDocType();

        if (str_ends_with($type, '[]')) {
            return 'array';
        }

        return $type;
    }

}
