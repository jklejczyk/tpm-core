<?php

declare(strict_types=1);

namespace Tpm\Shared;

enum Role
{
    case Operator;
    case Technician;
    case Manager;
}
