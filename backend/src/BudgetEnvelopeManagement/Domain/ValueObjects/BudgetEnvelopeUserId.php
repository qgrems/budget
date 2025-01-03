<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\ValueObjects;

use Assert\Assert;

final readonly class BudgetEnvelopeUserId
{
    private function __construct(protected string $uuid)
    {
        Assert::that($uuid)
            ->notBlank('UUID should not be blank.')
            ->uuid('Invalid UUID format.')
        ;
    }

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public function equals(BudgetEnvelopeUserId $budgetEnvelopeUserId): bool
    {
        return (string) $budgetEnvelopeUserId === $this->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
