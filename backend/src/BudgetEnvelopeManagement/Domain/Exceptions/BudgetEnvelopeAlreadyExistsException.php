<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Exceptions;

final class BudgetEnvelopeAlreadyExistsException extends \LogicException
{
    public const string MESSAGE = 'envelopes.alreadyExists';

    public function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
