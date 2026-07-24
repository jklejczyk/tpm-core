<?php

declare(strict_types=1);

namespace Tpm\WorkOrder\Exception;

final class MissingResolution extends \DomainException
{
    public static function forResolve(): self
    {
        return new self('Resolving a work order requires a resolution description.');
    }
}
