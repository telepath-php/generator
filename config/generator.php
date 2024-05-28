<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot API Documentation
    |--------------------------------------------------------------------------
    |
    | Path to the Telegram Bot API documentation
    |
    */

    'url' => 'https://core.telegram.org/bots/api',

    /*
    |--------------------------------------------------------------------------
    | Build Path
    |--------------------------------------------------------------------------
    |
    | The path in which the generated code should be placed.
    |
    */

    'build_path' => base_path('build/'),

    /*
    |--------------------------------------------------------------------------
    | Parsers
    |--------------------------------------------------------------------------
    |
    | This option controls the parsers that will be used to parse the
    | documentation for Telegram Bot API.
    |
    */

    'parser' => [

        \App\Parsers\TypeParser::class,

        \App\Parsers\MethodParser::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Generators
    |--------------------------------------------------------------------------
    |
    | This option controls the generators that will be used to generate the
    | code for Telepath.
    |
    */

    'generators' => [

        \App\Generators\ReplyToMessageTraitGenerator::class,
        \App\Generators\TypeGenerator::class,

        \App\Generators\EnumTypesGenerator::class,
        \App\Generators\MethodGenerator::class,

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

    'type' => [

        'namespace' => 'Telepath\\Telegram',

        'enum_namespace' => 'Telepath\\Types\\Enums',

        'parent_class' => 'Telepath\\Types\\Type',

        'factory_class' => 'Telepath\\Types\\Factory',

        'bot_class' => 'Telepath\\Bot',

        'parse_mode_enum' => 'Telepath\\Support\\ParseMode\\ParseMode',

        /*
        |--------------------------------------------------------------------------
        | Replacement Types
        |--------------------------------------------------------------------------
        |
        | Sometimes its necessary to replace a type with another type. This option
        | allows to do so. The key is the type to be ignored from the Telegram
        | docs and instead the value will be referenced.
        |
        */

        'replace_types' => [

            'InputFile' => 'Telepath\\Files\\InputFile',

        ],

        /*
        |--------------------------------------------------------------------------
        | Extensions
        |--------------------------------------------------------------------------
        |
        | Sometimes its necessary to add method to a generated Type to improve
        | the usability. This option controls the traits that will be added
        | to the corresponding types.
        |
        */

        'extensions' => [

            'Dice' => [
                'Telepath\\Types\\Extensions\\DiceExtension',
            ],

            'File' => [
                'Telepath\\Types\\Extensions\\FileExtension',
            ],

            'Message' => [
                'Telepath\\Types\\Extensions\\MessageExtension',
            ],

            'Update' => [
                'Telepath\\Types\\Extensions\\UpdateExtension',
            ],

        ],

    ],

    'method' => [

        'classname' => 'Telepath\\Layers\\Generated',

        'parent_class' => 'Telepath\\Layers\\Base',

        'exception' => 'Telepath\\Exceptions\\TelegramException',

    ],

    'extension' => [

        'namespace' => 'Telepath\\Types\\Extensions',

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
