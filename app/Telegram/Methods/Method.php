<?php

namespace App\Telegram\Methods;

use App\Php\Type;
use App\Support\ReturnTypeDiscovery;

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

    public function return(): ?Type
    {
        static $returnType;

        if ($returnType) {
            return new Type($returnType);
        }

        $returnType = match (config('tellaptepab.return_type_discovery_driver', 'local')) {

            'local'  => (new ReturnTypeDiscovery\LocalReturnTypeDiscovery())->discover($this),

            'openai' => (new ReturnTypeDiscovery\OpenAiReturnTypeDiscovery())->discover($this),

            default  => throw new \UnexpectedValueException('Invalid return type discovery driver'),

        };

        if (! $returnType) {
            return null;
        }

        return new Type($returnType);
    }


}
