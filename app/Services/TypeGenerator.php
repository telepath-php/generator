<?php

namespace App\Services;

use App\Telegram\Type;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use function PHPUnit\Framework\isNull;

class TypeGenerator
{

    public function generate(Type $type): string
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace($type->namespace);
        $class = $namespace->addClass($type->name);
        $class->setExtends($type->extends);

        foreach ($type->fields() as $field) {

            $property = $class->addProperty($field->name);
            $property->setType($field->phpType);
            $property->setPublic();

            if ($field->phpDocType !== null) {
                $property->addComment('@var ' . $namespace->simplifyType($field->phpDocType));
            }

        }

//        $construct = $class->addMethod('__construct');
//        $construct->addParameter('data')->setType('array')->setDefaultValue([]);
//        $construct->addBody('parent::__construct($data);');

        return (string) $file;
    }

}
