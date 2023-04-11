<?php

namespace App\Validators;

use App\Telegram\Document;
use App\Telegram\Methods\Method;
use App\Telegram\Types\Type;
use Illuminate\Support\Collection;

class ReturnTypeValidator extends Validator
{

    public function validate(Document $document): void
    {

        /** @var Collection<string, string> $returnTypes */
        $returnTypes = $document->methods->mapWithKeys(fn(Method $method) => [$method->name => $method->return()]);

        $availableTypes = $document->types->mapWithKeys(fn(Type $type) => [$type->name => $type]);
        $invalidTypes = new Collection();

        $returnTypes->each(function ($returnType, $name) use ($availableTypes, $invalidTypes) {

            $checkTypes = str($returnType)
                ->replace('Array of ', '')
                ->explode(' or ');

            foreach ($checkTypes as $checkType) {

                if (
                    ! in_array(strtolower($checkType), ['true', 'false', 'string', 'int'])
                    && ! $availableTypes->has($checkType)
                ) {

                    $invalidTypes->put($name, $returnType);

                }

            }

        });

        $messages = $invalidTypes->count() > 0
            ? str($invalidTypes
                ->map(fn($type, $name) => "  - Method $name has invalid return type: $type")
                ->join("\n"))->prepend("Errors:\n")
            : 'There were no errors.';

        $this->report(
            $messages,
            $returnTypes->toArray()
        );

        if ($invalidTypes->count() > 0) {

            $this->fail("Return type validation failed. There were {$invalidTypes->count()} invalid return types. See report for details.");

        }

    }

}
