<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\ValueObjects;

final readonly class UtcClock
{
    public static function immutableNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function now(): \DateTime
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public static function fromDateTime(\DateTime $dateTime): \DateTime
    {
        return $dateTime->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function fromDateTimeImmutable(\DateTimeImmutable $dateTime): \DateTimeImmutable
    {
        return $dateTime->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function fromStringToImmutable(string $dateTime): \DateTimeImmutable
    {
        return new \DateTimeImmutable($dateTime, new \DateTimeZone('UTC'));
    }

    public static function fromImmutableToDateTime(\DateTimeImmutable $dateTime): \DateTime
    {
        return \DateTime::createFromImmutable($dateTime)->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function fromDateTimeToString(\DateTime $dateTime): string
    {
        return $dateTime->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM);
    }

    public static function fromImmutableToString(\DateTimeImmutable $dateTime): string
    {
        return $dateTime->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM);
    }

    public static function fromStringToDateTime(string $dateTime): \DateTime
    {
        return new \DateTime($dateTime, new \DateTimeZone('UTC'));
    }
}
