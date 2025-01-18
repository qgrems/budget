<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Exceptions;

final class UserEncryptionException extends \RuntimeException
{
    private function __construct(
        string $message = 'users.encryptionError',
        int $code = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromGetKeyFailure(): self
    {
        return new self('error.get_key', 500);
    }

    public static function fromEncryptFailure(): self
    {
        return new self('error.encrypt', 500);
    }

    public static function fromDecryptFailure(): self
    {
        return new self('error.decrypt', 500);
    }
}
