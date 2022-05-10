<?php

namespace App\Parsers\Types;

enum InheritanceType
{
    case DEFAULT;

    case PARENT;

    case CHILD;
}
