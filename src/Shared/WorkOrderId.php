<?php

declare(strict_types=1);

namespace Tpm\Shared;

final readonly class WorkOrderId
{
    public function __construct(public string $value)
    {
    }
}
