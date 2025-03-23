<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\ValueObjects;

final readonly class UtcClock
{
    public static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
