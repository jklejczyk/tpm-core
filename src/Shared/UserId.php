<?php

declare(strict_types=1);

namespace Tpm\Shared;

final readonly class UserId
{
    public function __construct(public string $value)
    {
    }
}
