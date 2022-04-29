<?php

namespace App\Generators;

use App\Parsers\Parser;
use App\Telegram\Method;
use Nette\PhpGenerator\PhpFile;

class MethodGenerator
{

    protected PhpFile $file;

    protected \Nette\PhpGenerator\PhpNamespace $namespace;
    protected \Nette\PhpGenerator\ClassType $class;

    protected string $namespacePrefix = '';

    public function __construct(string $name, string $namespacePrefix = '')
    {
        $this->file = new PhpFile();
        $this->file->addComment('This file is auto-generated.');

        $split = str($name)->explode('\\');
        $this->namespace = $this->file->addNamespace($split->slice(0, -1)->join('\\'));
        $this->class = $this->namespace->addClass($split->last());

        $this->namespacePrefix = $namespacePrefix;
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

        $returnTypes = config('telegram.return_types');
        if (isset($returnTypes[$methodDefinition->name])) {
            $phpType = Parser::phpType($returnTypes[$methodDefinition->name], $this->namespacePrefix);
            $phpDocType = Parser::phpDocType($returnTypes[$methodDefinition->name], $this->namespacePrefix);

            $method->setReturnType($phpType);

            if ($phpType !== $phpDocType) {
                $method->addComment('@return ' .
                    $this->namespace->simplifyType($phpDocType)
                );
            }
        }
    }

    public function generate(): string
    {
        return (string) $this->file;
    }

}
