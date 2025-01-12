<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Inbound;

interface EventInterface
{
    public function toArray(): array;

    public static function fromArray(array $data): self;
}
