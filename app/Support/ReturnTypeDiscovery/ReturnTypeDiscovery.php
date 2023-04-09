<?php

namespace App\Support\ReturnTypeDiscovery;

use App\Telegram\Methods\Method;

abstract class ReturnTypeDiscovery
{

    abstract public function discover(Method $type): ?string;

}
