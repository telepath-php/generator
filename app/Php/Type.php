<?php

namespace App\Php;

use Nette\PhpGenerator\PhpNamespace;

class Type
{
    public string $phpType;

    public string $docType;

    public function __construct(
        public string $telegramType,
    ) {
        $this->docType = $this->buildDocType($telegramType);
        $this->phpType = $this->buildPhpType();
    }

    protected function fullyQualifiedClassname(string $type): string
    {
        $classMap = config('generator.type.replace_types');

        if (isset($classMap[$type])) {
            return $classMap[$type];
        }

        if (! str_contains($type, '\\')) {
            return config('generator.type.namespace').'\\'.$type;
        }

        return $type;
    }

    protected function buildDocType(string $type)
    {
        if (str_starts_with(strtolower($type), 'array of')) {
            $arrayType = $this->buildDocType(substr($type, 9));

            return str_contains($arrayType, '|')
                ? str_replace('|', '[]|', $arrayType).'[]'
                : $arrayType.'[]';
        }

        $parts = str($type)->split('/(?: or |, | and )/', 2);
        if (count($parts) > 1) {
            return $this->buildDocType($parts[0]).'|'.$this->buildDocType($parts[1]);
        }

        $fullyQualifiedClassname = $this->fullyQualifiedClassname($type);

        return match (strtolower($type)) {
            'string' => 'string',
            'integer', 'int' => 'int',
            'float', 'float number' => 'float',
            'boolean', 'true', 'false' => 'bool',
            default => $fullyQualifiedClassname,
        };
    }

    protected function buildPhpType()
    {
        if (str_ends_with($this->docType, '[]')) {
            return 'array';
        }

        return $this->docType;
    }

    public function shouldDefinePhpDoc(): bool
    {
        return $this->docType !== $this->phpType;
    }

    public function simplify(PhpNamespace $namespace, ?string $fqClassName = null): string
    {
        foreach (explode('|', $this->docType) as $type) {
            if (str_contains($type, '\\') && (! $fqClassName || $type !== $fqClassName)) {
                $namespace->addUse(rtrim($type, '[]'));
            }
        }

        return $namespace->simplifyType($this->docType);
    }

    public function prependType(string $phpType): void
    {
        $this->phpType = $phpType.'|'.$this->phpType;
        $this->docType = $phpType.'|'.$this->docType;
    }
}
