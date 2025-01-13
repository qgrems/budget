<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\ValueObjects;

use Assert\Assert;

final readonly class BudgetEnvelopeHistoryTransactionType
{
    public const string CREDIT = 'credit';
    public const string DEBIT = 'debit';

    private function __construct(protected string $budgetEnvelopeHistoryTransactionType)
    {
        Assert::that($budgetEnvelopeHistoryTransactionType)
            ->notBlank('Monetary type should not be blank.')
            ->string('Monetary type must be a string.')
            ->choice([self::CREDIT, self::DEBIT], 'The transaction type must be either "credit" or "debit".')
        ;
    }

    public static function fromString(string $budgetEnvelopeHistoryTransactionType): self
    {
        return new self($budgetEnvelopeHistoryTransactionType);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeHistoryTransactionType;
    }
}
