<?php

namespace App\Generators;

use App\Support\PhpTypeMapper;
use App\Telegram\Document;
use App\Telegram\Methods\Method;
use App\Telegram\Methods\Parameter;
use Illuminate\Support\Facades\File;
use JetBrains\PhpStorm\Language;
use Nette\PhpGenerator\Method as PhpMethod;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Nette\PhpGenerator\TraitType;

class ReplyToMessageTraitGenerator extends Generator
{

    public function generate(Document $document)
    {
        $sendMessageMethod = $document->methods->get('sendMessage');

        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace(config('tellaptepab.extension.namespace'));
        $trait = $namespace->addTrait('RepliesToMessage');

        $this->replyToUser($namespace, $trait, $sendMessageMethod);

        $this->replyToChat($namespace, $trait, $sendMessageMethod);

        $filename = psr_build_path($namespace->getName() . '\\' . $trait->getName());
        $content = (new PsrPrinter())->printFile($file);

        File::ensureDirectoryExists(dirname($filename));
        file_put_contents($filename, $content);
    }

    protected function replyToUser(PhpNamespace $namespace, TraitType $trait, Method $methodInfo): void
    {
        // Definition
        $method = $trait->addMethod('replyToUser');
        $relevantParameters = $methodInfo->parameters->filter(fn(Parameter $parameter) => ! in_array($parameter->name, ['chat_id']));

        // Body
        $method->addBody('return $this->sendMessage(');
        $method->addBody('    chat_id: $this->user()->id,');
        $relevantParameters->each(function (Parameter $parameter) use ($method) {
            return $method->addBody("    {$parameter->name}: \${$parameter->name},");
        });
        $method->addBody(');');

        // Parameters
        $relevantParameters->each(function (Parameter $parameter) use ($namespace, $method) {
            $this->addParameter($namespace, $method, $parameter);
        });

        // Return type
        $returnType = $methodInfo->return();
        $docType = PhpTypeMapper::docType($returnType);
        $phpType = PhpTypeMapper::phpType($returnType);
        $docType = PhpTypeMapper::simplifyType($namespace, $docType);

        $method->setReturnType($phpType);
        if ($docType !== $phpType) {
            $method->addComment("@return {$docType}");
        }
    }

    protected function replyToChat(PhpNamespace $namespace, TraitType $trait, Method $methodInfo): void {}

    protected function addParameter(PhpNamespace $namespace, PhpMethod $method, Parameter $parameter): void
    {
        $docType = PhpTypeMapper::docType($parameter->type);
        $phpType = PhpTypeMapper::phpType($parameter->type);

        $docType = PhpTypeMapper::simplifyType($namespace, $docType);

        $method->addComment("@param {$docType} \${$parameter->name} {$parameter->description}");

        $argument = $method->addParameter($parameter->name)
            ->setType($phpType);

        if ($parameter->optional()) {
            $argument->setNullable()->setDefaultValue(null);
        }
    }


}
