<?php

return [

    'url' => 'https://core.telegram.org/bots/api',

    'parser' => [

        \App\Parsers\TypeParser::class,
        \App\Parsers\MethodParser::class,

    ],

    'namespace' => 'Telepath\\Telegram',

    'parent_class' => 'Telepath\\Types\\Type',

    'factory_class' => 'Telepath\\Types\\Factory',

    'bot_class' => 'Telepath\\Bot',

    'generators' => [

        \App\Generators\TypeGenerator::class,

    ],

    'replace_types' => [

        'InputFile' => 'Telepath\\Types\\InputFile',

    ],

];
