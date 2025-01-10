<?php

namespace App\UserManagement\Domain\Exceptions;

final class UserOldPasswordIsIncorrectException extends \Exception
{
    public const string MESSAGE = 'users.oldPasswordIsIncorrect';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
