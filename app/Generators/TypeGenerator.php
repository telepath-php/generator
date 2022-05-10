<?php

namespace App\Generators;

use App\Parsers\Types\InheritanceType;
use App\Telegram\Types\Type;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

class TypeGenerator
{

    public function generate(Type $type): string
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace($type->namespace);

        $class = $namespace->addClass($type->name)
            ->setExtends($type->extends)
            ->addComment($type->description);

        if ($type->inheritanceType === InheritanceType::PARENT) {
            $class->setAbstract();
        } else {
            $this->createMakeMethod($namespace, $class, $type);
        }

        $this->createProperties($namespace, $class, $type);

        return (string) $file;
    }

    protected function createProperties(PhpNamespace $namespace, ClassType $class, Type $type)
    {
        foreach ($type->fields as $field) {

            $phpDocType = $field->phpDocType();
            $phpType = $field->phpType();

            $property = $class->addProperty($field->name)->setType($phpType)
                ->addComment($field->description);

            if ($field->optional()) {
                $property->setNullable()->setInitialized();
            }

            if ($phpDocType !== $phpType) {
                $property->addComment('@var ' . $namespace->simplifyType($phpDocType));
            }

        }
    }

    protected function createMakeMethod(PhpNamespace $namespace, ClassType $class, Type $type)
    {
        $makeMethod = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('static');
        $makeMethod->addBody('return new static([');

        foreach ($type->fields as $field) {

            $phpDocType = $field->phpDocType();
            $phpType = $field->phpType();

            $makeMethod->addComment('@param ' . $namespace->simplifyType($phpDocType)
                . ' $' . $field->name . ' ' . $field->description);

            $parameter = $makeMethod->addParameter($field->name)->setType($phpType);

            if ($field->optional()) {
                $parameter->setNullable()->setDefaultValue(null);
            }

            $makeMethod->addBody("    ? => \${$field->name},", [$field->name]);

        }

        $makeMethod->addBody(']);');
    }

}
