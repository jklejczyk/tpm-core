<?php

declare(strict_types=1);

namespace Tpm\WorkOrder\Exception;

final class MissingHoldReason extends \DomainException
{
    public static function forHold(): self
    {
        return new self('Putting a work order on hold requires a reason.');
    }
}
