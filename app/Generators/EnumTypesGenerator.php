<?php

namespace App\Generators;

use App\OpenAi\EnumTypeParser;
use App\Telegram\Document;
use App\Telegram\Methods\Method;
use App\Telegram\Methods\Parameter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class EnumTypesGenerator extends Generator
{
    public function generate(Document $document)
    {
        $parameters = $this->parameters($document->methods);

        /**
         * @var Method $method
         * @var Parameter $parameter
         */
        foreach ($parameters as ['method' => $method, 'parameter' => $parameter]) {

            ['name' => $name, 'values' => $values] = resolve(EnumTypeParser::class)
                ->parse($method->name, $parameter->name, $parameter->description);

            $enumName = $this->generateEnumType($name, $values);
            $parameter->type->prependType($enumName);

        }
    }

    /**
     * @param  Collection<Method>  $methods
     * @return array{method: Method, parameter: Parameter}
     */
    protected function parameters(Collection $methods): array
    {
        $enumTypes = [];
        foreach ($methods as $method) {
            $parameters = $method->parameters->filter(fn (Parameter $parameter) => str_contains($parameter->description, 'Type of'));

            foreach ($parameters as $parameter) {
                $enumTypes[] = [
                    'method' => $method,
                    'parameter' => $parameter,
                ];
            }
        }

        return $enumTypes;
    }

    /**
     * @param  array<array{name: string, comment: string}>  $values
     */
    protected function generateEnumType(string $name, array $values): string
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated.');

        $namespace = $file->addNamespace(config('generator.type.enum_namespace'));

        $enum = $namespace->addEnum($name);
        $enum->setType('string');

        foreach ($values as $value) {
            $case = str($value['name'])->camel()->ucfirst()->toString();
            $enum->addCase($case, $value['name'])
                ->addComment($value['comment']);
        }

        $content = (new PsrPrinter())->printFile($file);

        $fullyQualifiedName = $namespace->getName().'\\'.$enum->getName();
        $filename = psr_build_path($fullyQualifiedName);

        File::ensureDirectoryExists(dirname($filename));
        file_put_contents($filename, $content);
        $this->runPint($filename);

        return $fullyQualifiedName;
    }
}
