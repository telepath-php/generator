<?php

return [

    'url' => 'https://core.telegram.org/bots/api',

    'parent_class' => 'Telepath\\Telegram\\',

    'parser' => [

        App\Parsers\TypeParser::class,
        App\Parsers\MethodParser::class,

    ],

];
