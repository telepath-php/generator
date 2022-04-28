<?php

namespace App\Services;

use App\Telegram\Method;
use Nette\PhpGenerator\PhpFile;

class MethodGenerator
{

    protected PhpFile $file;

    protected \Nette\PhpGenerator\PhpNamespace $namespace;
    protected \Nette\PhpGenerator\ClassType $class;

    public function __construct(string $name)
    {
        $this->file = new PhpFile();
        $this->file->addComment('This file is auto-generated.');

        $split = str($name)->explode('\\');
        $this->namespace = $this->file->addNamespace($split->slice(0, -1)->join('\\'));
        $this->class = $this->namespace->addClass($split->last());
    }

    public function addMethod(Method $methodDefinition)
    {
        $method = $this->class->addMethod($methodDefinition->name)
            ->setStatic()
            ->addComment($methodDefinition->description . "\n");

        foreach ($methodDefinition->parameter as $parameterDefinition) {
            foreach (explode('|', $parameterDefinition->phpDocType()) as $type) {
                if (str_contains($type, '\\')) {
                    $this->namespace->addUse(rtrim($type, '[]'));
                }
            }

            $phpDocType = $this->namespace->simplifyType($parameterDefinition->phpDocType());
            $method->addComment("@param {$phpDocType} \${$parameterDefinition->name} {$parameterDefinition->description}");

            $property = $method->addParameter($parameterDefinition->name)
                ->setType($parameterDefinition->phpType());

            if (! $parameterDefinition->required()) {
                $property->setNullable();
                $property->setDefaultValue(null);
            }
        }
    }

    public function generate(): string
    {
        return (string) $this->file;
    }

}
