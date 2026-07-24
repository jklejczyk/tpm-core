<?php

declare(strict_types=1);

namespace Tpm\WorkOrder\Exception;

use Tpm\Shared\Actor;

final class UnauthorizedTransition extends \DomainException
{
    public static function for(Actor $actor, string $action): self
    {
        return new self(
            "Actor '{$actor->id->value}' with role '{$actor->role->name}' may not '{$action}' this work order.",
        );
    }
}
