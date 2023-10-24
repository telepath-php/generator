<?php

namespace App\Php;

use Nette\PhpGenerator\PhpNamespace;

readonly class Type
{

    public string $phpType;

    public string $docType;

    public function __construct(
        public string $telegramType,
    ) {
        $this->docType = $this->buildDocType($telegramType);
        $this->phpType = $this->buildPhpType();
    }

    protected function buildDocType(string $type)
    {
        if (str_starts_with(strtolower($type), 'array of')) {
            $arrayType = $this->buildDocType(substr($type, 9));

            return str_contains($arrayType, '|')
                ? str_replace('|', '[]|', $arrayType) . '[]'
                : $arrayType . '[]';
        }

        $parts = str($type)->split('/(?: or |, | and )/', 2);
        if (count($parts) > 1) {
            return $this->buildDocType($parts[0]) . '|' . $this->buildDocType($parts[1]);
        }

        $classMap = config('tellaptepab.type.replace_types');
        $fullyQualifiedClassname = isset($classMap[$type])
            ? $classMap[$type]
            : config('tellaptepab.type.namespace') . '\\' . $type;

        return match (strtolower($type)) {
            'string'                   => 'string',
            'integer', 'int'           => 'int',
            'float', 'float number'    => 'float',
            'boolean', 'true', 'false' => 'bool',
            default                    => $fullyQualifiedClassname,
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


}
