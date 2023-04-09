<?php

namespace App\Support;

class PhpTypeMapper
{

    public static function docType(string $type): string
    {
        if (str_starts_with(strtolower($type), 'array of')) {
            $arrayType = static::docType(substr($type, 9));

            return str_contains($arrayType, '|')
                ? str_replace('|', '[]|', $arrayType) . '[]'
                : $arrayType . '[]';
        }

        $parts = str($type)->split('/(?: or |, | and )/', 2);
        if (count($parts) > 1) {
            return static::docType($parts[0]) . '|' . static::docType($parts[1]);
        }

        $classMap = config('tellaptepab.replace_types');
        $fullyQualifiedClassname = isset($classMap[$type])
            ? $classMap[$type]
            : config('tellaptepab.namespace') . '\\' . $type;

        return match (strtolower($type)) {
            'string'                   => 'string',
            'integer', 'int'           => 'int',
            'float', 'float number'    => 'float',
            'boolean', 'true', 'false' => 'bool',
            default                    => $fullyQualifiedClassname,
        };

    }

    public static function phpType(string $type): string
    {
        $type = static::docType($type);

        if (str_ends_with($type, '[]')) {
            return 'array';
        }

        return $type;
    }

}
