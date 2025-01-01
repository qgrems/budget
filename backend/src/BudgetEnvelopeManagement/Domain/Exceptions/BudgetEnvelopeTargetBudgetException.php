<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Exceptions;

final class BudgetEnvelopeTargetBudgetException extends \LogicException
{
    private function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function isBelowZero(): self
    {
        return new self(
            'envelopes.targetBudgetIsBelowZero',
            400,
        );
    }
}
