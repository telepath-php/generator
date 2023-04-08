<?php

namespace App\Generators;

use App\Telegram\Document;
use App\Telegram\Types\Field;
use App\Telegram\Types\FieldList;
use App\Telegram\Types\Type;
use Illuminate\Support\Facades\File;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

class TypeGenerator extends Generator
{

    public function generate(Document $document)
    {
        File::ensureDirectoryExists(base_path('build/Types'));

        foreach ($document->types as $type) {
            $content = $this->generateType($type);

            file_put_contents(base_path("build/Types/{$type->name}.php"), $content);
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

        // TODO: Traits

        if ($type->children->count() > 0) {
            $class->setAbstract();

            $factory = config('tellaptepab.factory_class');
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
        foreach ($fields as $field) {

            if (! $field->property) {
                continue;
            }

            $docType = $field->docType();
            $phpType = $field->phpType();

            $property = $class->addProperty($field->name)
                ->setType($field->phpType())
                ->addComment($field->description);

            if ($docType !== $phpType) {
                $property->addComment('@var ' . $namespace->simplifyType($docType));
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
        $method = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('static');
        $method->addBody('return new static([');

        /** @var Field $field */
        foreach ($type->fields as $field) {

            if ($field->value()) {
                continue;
            }

            $docType = $field->docType();
            $phpType = $field->phpType();

            $method->addComment("@param {$namespace->simplifyType($docType)} \${$field->name} {$field->description}");

            $parameter = $method->addParameter($field->name)
                ->setType($phpType);

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

        $botClass = config('tellaptepab.bot_class');
        $namespace->addUse($botClass);
        $method->addParameter('bot')
            ->setDefaultValue(null)
            ->setType($botClass);

        $method->addBody('return match($data[?]) {', [$type->childIdentifier()]);
        ray(iterator_to_array($type->childMap()))->label($type->name);
        foreach ($type->childMap() as $value => $child) {
            $namespace->addUse($child->className());
            $class = $namespace->simplifyType($child->className());

            $method->addBody("    ? => new {$class}(\$data, \$bot),", [$value]);
        }
        $method->addBody('};');
    }


}
