<?php

namespace App\Generators;

use App\Telegram\Type;
use Nette\PhpGenerator\PhpFile;

class TypeGenerator
{

    public function generate(Type $type): string
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace($type->namespace);
        $class = $namespace->addClass($type->name);
        $class->setExtends($type->extends);
        $class->addComment($type->description);

        $makeMethod = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('static');

        $makeMethod->addBody('return new static([');

        foreach ($type->fields as $field) {

            $property = $class->addProperty($field->name)
                ->setType($field->phpType)
                ->addComment($field->description);

            $makeMethod->addComment('@param ' . $namespace->simplifyType($field->phpDocType ?? $field->phpType)
                . ' $' . $field->name . ' ' . $field->description);
            $parameter = $makeMethod->addParameter($field->name)
                ->setType($field->phpType);

            if ($field->optional) {
                $property->setNullable()->setInitialized();
                $parameter->setNullable()->setDefaultValue(null);
            }

            if ($field->phpDocType !== null) {
                $property->addComment('@var ' . $namespace->simplifyType($field->phpDocType));
            }

            $makeMethod->addBody("    ? => \${$field->name},", [$field->name]);

        }

        $makeMethod->addBody(']);');

        return (string) $file;
    }

}
