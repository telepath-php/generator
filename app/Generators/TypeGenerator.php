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

        foreach ($type->fields as $field) {

            $property = $class->addProperty($field->name);
            $property->setType($field->phpType);
            $property->setPublic();
            $property->addComment($field->description);

            if ($field->optional) {
                $property->setNullable();
            }

            if ($field->phpDocType !== null) {
                $property->addComment('@var ' . $namespace->simplifyType($field->phpDocType));
            }

        }

        return (string) $file;
    }

}
