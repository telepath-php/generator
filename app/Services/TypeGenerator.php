<?php

namespace App\Services;

use App\Telegram\Type;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use function PHPUnit\Framework\isNull;

class TypeGenerator
{

    public function generate(Type $type)
    {
        $properties = [];
        foreach ($type->fields() as $field) {
            $property = new PropertyGenerator(
                name: $field->name,
                flags: PropertyGenerator::FLAG_PUBLIC | PropertyGenerator::FLAG_READONLY
            );

            $property->omitDefaultValue();

            if ($field->phpDocType !== null) {
                $property->setDocBlock(new DocBlockGenerator(
                    tags: [['name' => 'var', 'description' => $field->phpDocType]]
                ));
            }

            $properties[] = $property;
        }

        $file = FileGenerator::fromArray([
            'classes'  => [
                new ClassGenerator(
                    name: $type->name,
                    namespaceName: $type->namespace,
                    flags: null,
                    extends: $type->extends,
                    interfaces: [],
                    properties: $properties
                )
            ],
            'docblock' => new DocBlockGenerator(
                shortDescription: 'This file was automatically generated!'
            )
        ]);

        $text = $file->generate();

        foreach ($type->fields() as $field) {
            $text = str_replace(
                search: '$' . $field->name . ';',
                replace: $field->phpType . ' $' . $field->name . ';',
                subject: $text
            );
        }

        return $text;
    }

}
