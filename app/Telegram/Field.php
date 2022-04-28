<?php

namespace App\Telegram;

class Field
{
    public bool $optional;

    public string $phpType;

    public ?string $phpDocType;

    public function __construct(
        public string $name,
        public string $type,
        public string $description,
        protected string $namespace
    ) {
        $this->optional = $this->isOptional();
        $this->phpType = $this->getPhpType($this->type);
        $phpDocType = $this->getPhpDocType($this->type);

        if ($phpDocType !== $this->phpType) {
            $this->phpDocType = $phpDocType;
        } else {
            $this->phpDocType = null;
        }
    }

    protected function isOptional(): bool
    {
        return str_starts_with($this->description, 'Optional');
    }

    protected function getPhpType(string $type): string
    {
        if (str_starts_with($type, 'Array of')) {
            return 'array';
        }

        $orParts = explode(' or ', $type, 2);
        if (count($orParts) > 1) {
            return $this->getPhpType($orParts[0]) . '|' . $this->getPhpType($orParts[1]);
        }

        return match ($type) {
            'String'                   => 'string',
            'Integer'                  => 'int',
            'Float', 'Float number'    => 'float',
            'Boolean', 'True', 'False' => 'bool',
            default                    => $this->namespace . '\\' . $type,
        };
    }

    protected function getPhpDocType(string $type): ?string
    {
        if (str_starts_with($type, 'Array of')) {
            $subType = substr($type, 9);
            return $this->getPhpDocType($subType) . '[]';
        }

        $orParts = explode(' or ', $type, 2);
        if (count($orParts) > 1) {
            return $this->getPhpDocType($orParts[0]) . '|' . $this->getPhpDocType($orParts[1]);
        }

        return match ($type) {
            'String'                   => 'string',
            'Integer'                  => 'int',
            'Float', 'Float number'    => 'float',
            'Boolean', 'True', 'False' => 'bool',
            default                    => $this->namespace . '\\' . $type,
        };
    }

}
