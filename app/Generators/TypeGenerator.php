<?php

namespace App\Generators;

use App\Telegram\Document;
use App\Telegram\Types\Field;
use App\Telegram\Types\FieldList;
use App\Telegram\Types\Type;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

class TypeGenerator extends Generator
{

    public function generate(Document $document)
    {
        foreach ($document->types as $type) {
            $filename = psr_build_path($type->className());
            $content = $this->generateType($type);

            File::ensureDirectoryExists(dirname($filename));
            file_put_contents($filename, $content);

            $this->runPint($filename);
        }
    }

    protected function generateType(Type $type): string
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace($type->namespace());

        $namespace->addUse($type->parentClassName());

        $class = $namespace->addClass($type->name)
            ->setExtends($type->parentClassName())
            ->addComment($type->description);

        $extensions = config('tellaptepab.type.extensions');
        foreach ($extensions[$type->name] ?? [] as $trait) {
            $namespace->addUse($trait);
            $class->addTrait($trait);
        }

        if ($type->isParent()) {
            $class->setAbstract();

            $factory = config('tellaptepab.type.factory_class');
            $namespace->addUse($factory);
            $class->addImplement($factory);

            $this->createFactoryMethod($namespace, $class, $type);
        } else {
            $this->createMakeMethod($namespace, $class, $type);
        }
        $this->createProperties($namespace, $class, $type->fields);

        return (new PsrPrinter)->printFile($file);

    }

    protected function createProperties(PhpNamespace $namespace, ClassType $class, FieldList $fields)
    {
        $fqClassName = $namespace->getName() . '\\' . $class->getName();

        foreach ($fields as $field) {

            if (! $field->property) {
                continue;
            }

            // We needed to pull this up so the classes get imported into the namespace
            // by simplifyDocType() before it gets added with setType() on the actual property
            $simplifiedDocType = $field->type->simplify($namespace, $fqClassName);

            ray($field->type->phpType);
            $property = $class->addProperty($field->name)
                ->setType($field->type->phpType)
                ->addComment($field->description);

            if ($field->type->shouldDefinePhpDoc()) {
                $property->addComment('@var ' . $simplifiedDocType);
            }

            if ($field->optional()) {
                $property->setNullable()->setInitialized();
            } elseif ($field->value()) {
                $property->setValue($field->value());
            }

        }
    }

    protected function createMakeMethod(PhpNamespace $namespace, ClassType $class, Type $type)
    {
        $fqClassName = $namespace->getName() . '\\' . $class->getName();

        $method = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('static');
        $method->addBody('return new static([');

        /** @var Field $field */
        foreach ($type->fields as $field) {

            if ($field->value() && ! $field->optional()) {
                continue;
            }

            $docType = $field->type->simplify($namespace, $fqClassName);
            $method->addComment("@param {$docType} \${$field->name} {$field->description}");

            $parameter = $method->addParameter($field->name)
                ->setType($field->type->phpType);

            if ($field->optional()) {
                $parameter->setNullable()->setDefaultValue(null);
            }

            $method->addBody("    ? => \${$field->name},", [$field->name]);

        }

        $method->addBody(']);');
    }

    protected function createFactoryMethod(PhpNamespace $namespace, ClassType $class, Type $type)
    {
        $method = $class->addMethod('factory')
            ->setStatic()
            ->setReturnType('self');

        $method->addParameter('data')
            ->setType('array');

        $botClass = config('tellaptepab.type.bot_class');
        $namespace->addUse($botClass);
        $method->addParameter('bot')
            ->setDefaultValue(null)
            ->setType($botClass);

        $method->addBody('return match($data[?]) {', [$type->childIdentifier()]);
        foreach ($type->childMap() as $value => $child) {
            $namespace->addUse($child->className());
            $class = $namespace->simplifyType($child->className());

            $method->addBody("    ? => new {$class}(\$data, \$bot),", [$value]);
        }
        $method->addBody('};');
    }


}
