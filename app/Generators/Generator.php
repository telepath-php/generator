<?php

namespace App\Generators;

use App\Telegram\Document;

abstract class Generator
{

    abstract public function generate(Document $document);

}
