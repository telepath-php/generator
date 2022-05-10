<?php

namespace App\Generators;

use App\Parsers\Types\InheritanceType;
use App\Telegram\Types\Type;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

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
            $this->createFactoryMethod($namespace, $class, $type);
        } else {
            $this->createMakeMethod($namespace, $class, $type);
        }

        $this->createProperties($namespace, $class, $type);

        return (string) $file;
    }

    protected function createProperties(PhpNamespace $namespace, ClassType $class, Type $type)
    {
        foreach ($type->fields as $field) {
            if (! $field->property) {
                continue;
            }

            $phpDocType = $field->phpDocType();
            $phpType = $field->phpType();

            $property = $class->addProperty($field->name)
                ->setType($phpType)
                ->addComment($field->description);

            if ($field->fixedValue !== null) {
                $property->setValue($field->fixedValue);
            }

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

            if (! $field->staticParameter) {
                continue;
            }

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

    protected function createFactoryMethod(PhpNamespace $namespace, ClassType $class, Type $type)
    {
        $factoryMethod = $class->addMethod('factory')
            ->setStatic()
            ->setReturnType('static');

        $factoryMethod->addParameter('data')
            ->setType('array');

        $factoryMethod->addBody('return match($data[?]) {', [$type->factoryField]);

        foreach ($type->factoryAssociation as $value => $class) {
            $namespace->addUse($class);
            $class = $namespace->simplifyType($class);

            $factoryMethod->addBody("\t? => new {$class}(\$data)", [$value]);
        }
        $factoryMethod->addBody('};');
    }

}
