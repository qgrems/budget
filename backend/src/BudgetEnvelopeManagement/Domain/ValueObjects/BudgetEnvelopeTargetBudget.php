<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\ValueObjects;

use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeTargetBudgetException;
use Assert\Assert;

final readonly class BudgetEnvelopeTargetBudget
{
    private function __construct(protected string $budgetEnvelopeTargetBudget)
    {
        if (floatval($budgetEnvelopeTargetBudget) <= 0) {
            throw BudgetEnvelopeTargetBudgetException::isBelowZero();
        }

        Assert::that($budgetEnvelopeTargetBudget)
            ->notBlank('Target budget should not be blank.')
            ->string('The target budget must be a string.')
            ->minLength(1, 'The target budget must be at least 1 character long.')
            ->maxLength(13, 'The target budget must be at most 13 character long.')
            ->regex('/^\d+(\.\d{2})?$/', 'The target budget must be a string representing a number with up to two decimal places (e.g., "0.00").')
        ;
    }

    public static function create(string $budgetEnvelopeTargetBudget): self
    {
        return new self($budgetEnvelopeTargetBudget);
    }

    public function toString(): string
    {
        return $this->budgetEnvelopeTargetBudget;
    }
}
