<?php

namespace App\Telegram\Methods;

class Method
{

    public readonly ?ParameterList $parameters;

    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {
        $this->parameters = new ParameterList();
    }

    public function importParameters(array $parameters)
    {
        foreach ($parameters as $parameter) {
            $this->parameters->add(
                new Parameter(
                    $parameter['parameter'],
                    $parameter['type'],
                    $parameter['required'],
                    $parameter['description'],
                )
            );
        }
    }


}
