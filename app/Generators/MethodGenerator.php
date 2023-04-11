<?php

namespace App\Generators;

use App\Support\PhpTypeMapper;
use App\Telegram\Document;
use App\Telegram\Methods\Method;
use Illuminate\Support\Facades\File;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

class MethodGenerator extends Generator
{

    public function generate(Document $document)
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $className = config('tellaptepab.method.classname');

        $namespace = $file->addNamespace(substr($className, 0, strrpos($className, '\\')));

        $class = $namespace->addClass(substr($className, strrpos($className, '\\') + 1))
            ->setExtends(config('tellaptepab.method.parent_class'))
            ->setAbstract();

        foreach ($document->methods as $method) {

            $this->addMethod($namespace, $class, $method);

        }

        $filename = psr_build_path($className);
        $content = (new PsrPrinter())->printFile($file);

        File::ensureDirectoryExists(dirname($filename));
        file_put_contents($filename, $content);
    }

    protected function addMethod(PhpNamespace $namespace, ClassType $class, Method $method)
    {
        // Definition
        $classMethod = $class->addMethod($method->name)
            ->addComment($method->description . "\n");

        // Body
        $classMethod->addBody('return $this->raw(?, func_get_args());', [$method->name]);

        // Parameters
        foreach ($method->parameters as $parameter) {
            $this->addParameter($namespace, $classMethod, $parameter);
        }

        // Return type
        $returnType = $method->return();
        $docType = PhpTypeMapper::docType($returnType);
        $phpType = PhpTypeMapper::phpType($returnType);
        $docType = $this->simplifyType($namespace, $docType);

        $classMethod->setReturnType($phpType);
        if ($docType !== $phpType) {
            $classMethod->addComment("@return {$docType}");
        }

        $exceptionClass = config('tellaptepab.method.exception');
        $namespace->addUse($exceptionClass);
        $classMethod->addComment('@throws ' . $namespace->simplifyType(config('tellaptepab.method.exception')));

    }

    protected function addParameter(PhpNamespace $namespace, \Nette\PhpGenerator\Method $classMethod, \App\Telegram\Methods\Parameter $parameter): void
    {
        $docType = PhpTypeMapper::docType($parameter->type);
        $phpType = PhpTypeMapper::phpType($parameter->type);

        $docType = $this->simplifyType($namespace, $docType);

        $classMethod->addComment("@param {$docType} \${$parameter->name} {$parameter->description}");

        $argument = $classMethod->addParameter($parameter->name)
            ->setType($phpType);

        if ($parameter->optional()) {
            $argument->setNullable()
                ->setDefaultValue(null);
        }
    }

    protected function simplifyType(PhpNamespace $namespace, string $docType): string
    {
        foreach (explode('|', $docType) as $type) {
            if (str_contains($type, '\\')) {
                $namespace->addUse(rtrim($type, '[]'));
            }
        }

        return $namespace->simplifyType($docType);
    }

}
