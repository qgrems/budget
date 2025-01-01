<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Exceptions;

final class BudgetEnvelopeNotFoundException extends \Exception
{
    public const string MESSAGE = 'envelopes.notFound';

    public function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
