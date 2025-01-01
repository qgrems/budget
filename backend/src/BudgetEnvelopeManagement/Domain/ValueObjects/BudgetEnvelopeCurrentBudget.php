<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\ValueObjects;

use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeCurrentBudgetException;
use Assert\Assert;

final readonly class BudgetEnvelopeCurrentBudget
{
    private function __construct(
        protected string $budgetEnvelopeCurrentBudget,
        protected string $budgetEnvelopeTargetBudget,
    ) {
        $currentBudgetFloat = floatval($budgetEnvelopeCurrentBudget);

        if ($currentBudgetFloat < 0.00) {
            throw BudgetEnvelopeCurrentBudgetException::exceedsDebitLimit();
        }

        if ($currentBudgetFloat > floatval($budgetEnvelopeTargetBudget)) {
            throw BudgetEnvelopeCurrentBudgetException::exceedsCreditLimit();
        }

        Assert::that($budgetEnvelopeCurrentBudget)
            ->notBlank('Current budget should not be blank.')
            ->string('Current budget must be a string.')
            ->minLength(1, 'The current budget must be at least 1 character long.')
            ->maxLength(13, 'The current budget must be at most 13 character long.')
            ->regex('/^\d+(\.\d{2})?$/', 'The current budget must be a string representing a number with up to two decimal places (e.g., "0.00").')
        ;
    }

    public static function create(string $budgetEnvelopeCurrentBudget, string $budgetEnvelopeTargetBudget): self
    {
        return new self($budgetEnvelopeCurrentBudget, $budgetEnvelopeTargetBudget);
    }

    public function toString(): string
    {
        return $this->budgetEnvelopeCurrentBudget;
    }
}
