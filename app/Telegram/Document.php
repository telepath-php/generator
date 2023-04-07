<?php

namespace App\Telegram;

use App\Telegram\Methods\Method;
use App\Telegram\Types\Type;
use Illuminate\Support\Collection;

class Document
{

    /** @var Collection<Type> */
    public Collection $types;

    /** @var Collection<Method> */
    public Collection $methods;

    public function __construct()
    {
        $this->types = new Collection();
        $this->methods = new Collection();
    }

}
