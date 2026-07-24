<?php

declare(strict_types=1);

namespace Tpm\WorkOrder\Exception;

use Tpm\WorkOrder\WorkOrderStatus;

final class IllegalStateTransition extends \DomainException
{
    public static function from(WorkOrderStatus $status, string $action): self
    {
        return new self("Cannot '{$action}' a work order in state '{$status->value}'.");
    }
}
