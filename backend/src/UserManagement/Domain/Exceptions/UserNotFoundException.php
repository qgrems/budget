<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Exceptions;

final class UserNotFoundException extends \Exception
{
    public const string MESSAGE = 'users.notFound';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 404,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
