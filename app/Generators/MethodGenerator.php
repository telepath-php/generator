<?php

namespace App\Generators;

use App\Telegram\Document;
use App\Telegram\Methods\Method;
use App\Telegram\Methods\Parameter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Nette\PhpGenerator\ClassType as PhpClass;
use Nette\PhpGenerator\Method as PhpMethod;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

class MethodGenerator extends Generator
{

    public function generate(Document $document)
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $className = config('generator.method.classname');

        $namespace = $file->addNamespace(substr($className, 0, strrpos($className, '\\')));

        $class = $namespace->addClass(substr($className, strrpos($className, '\\') + 1))
            ->setExtends(config('generator.method.parent_class'))
            ->setAbstract();

        foreach ($document->methods as $method) {

            $this->addMethod($namespace, $class, $method);

        }

        $filename = psr_build_path($className);
        $content = (new PsrPrinter())->printFile($file);

        File::ensureDirectoryExists(dirname($filename));
        file_put_contents($filename, $content);

        $this->runPint($filename);
    }

    protected function addMethod(PhpNamespace $namespace, PhpClass $class, Method $method)
    {
        // Definition
        $classMethod = $class->addMethod($method->name)
            ->addComment($method->description . "\n");

        // Body
        $classMethod->addBody('return $this->raw(?, func_get_args());', [$method->name]);

        // Parameters
        foreach ($method->parameters->sortByDesc->required as $parameter) {
            $this->addParameter($namespace, $classMethod, $parameter);
        }

        // Return type
        $returnType = $method->return();

        $docType = $returnType->simplify($namespace);
        $classMethod->setReturnType($returnType->phpType);

        if ($returnType->shouldDefinePhpDoc()) {
            $classMethod->addComment("@return {$docType}");
        }

        $exceptionClass = config('generator.method.exception');
        $namespace->addUse($exceptionClass);
        $classMethod->addComment('@throws ' . $namespace->simplifyType(config('generator.method.exception')));
    }

    protected function addParameter(PhpNamespace $namespace, PhpMethod $classMethod, Parameter $parameter): void
    {
        $docType = $parameter->type->simplify($namespace);

        $classMethod->addComment("@param {$docType} \${$parameter->name} {$parameter->description}");

        $argument = $classMethod->addParameter($parameter->name)
            ->setType($parameter->type->phpType);

        if ($parameter->optional()) {
            $argument->setNullable()
                ->setDefaultValue(null);
        }
    }

}
