<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Domain\Exceptions;

final class EnvelopeNameAlreadyExistsForUserException extends \LogicException
{
    public const string MESSAGE = 'envelopes.nameAlreadyExistsForUser';

    public function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
