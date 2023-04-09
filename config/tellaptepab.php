<?php

return [

    'url' => 'https://core.telegram.org/bots/api',

    'parser' => [

        \App\Parsers\TypeParser::class,
        \App\Parsers\MethodParser::class,

    ],

    'generators' => [

        \App\Generators\TypeGenerator::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Validators
    |--------------------------------------------------------------------------
    |
    | This option controls the validators that will be used to validate the
    | generated types and methods before generating code.
    |
    */

    'validators' => [

        App\Validators\ReturnTypeValidator::class,

    ],

    'namespace' => 'Telepath\\Telegram',

    'parent_class' => 'Telepath\\Types\\Type',

    'factory_class' => 'Telepath\\Types\\Factory',

    'bot_class' => 'Telepath\\Bot',

    'replace_types' => [

        'InputFile' => 'Telepath\\Types\\InputFile',

    ],

    'extensions' => [

        'Dice'    => [
            'Telepath\\Types\\Extensions\\DiceExtension',
        ],
        'File'    => [
            'Telepath\\Types\\Extensions\\FileExtension',
        ],
        'Message' => [
            'Telepath\\Types\\Extensions\\MessageExtension',
        ],
        'Update'  => [
            'Telepath\\Types\\Extensions\\UpdateExtension',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Return Type Discovery Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the return type discovery driver that will be used
    | to determine the return type of the methods.
    |
    | Possible values: local, openai
    |
    */

    'return_type_discovery_driver' => env('RETURN_TYPE_DISCOVERY_DRIVER', 'local'),

];
