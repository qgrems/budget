<?php

namespace App\SharedContext\Domain\Ports\Inbound;

interface AggregateIdInterface
{
    public static function fromString(string $uuid): self;

    public function __toString(): string;
}
