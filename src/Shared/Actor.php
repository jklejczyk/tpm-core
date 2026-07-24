<?php

declare(strict_types=1);

namespace Tpm\Shared;

final readonly class Actor
{
    public function __construct(
        public UserId $id,
        public Role $role,
    ) {
    }
}
