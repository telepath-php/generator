<?php

namespace App\Services;

use App\Telegram\Type;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

class TypeGenerator
{

    public function generate(Type $type): string
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace($type->namespace);
        $class = $namespace->addClass($type->name);
        $class->setExtends($type->extends);

        foreach ($type->fields as $field) {

            $property = $class->addProperty($field->name);
            $property->setType($field->phpType);
            $property->setPublic();
            $property->addComment($field->description);

            if ($field->phpDocType !== null) {
                $property->addComment('@var ' . $namespace->simplifyType($field->phpDocType));
            }

        }

        return (string) $file;
    }

}
