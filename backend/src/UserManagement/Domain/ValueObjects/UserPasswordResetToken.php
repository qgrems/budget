<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObjects;

final readonly class UserPasswordResetToken
{
    private function __construct(protected string $passwordResetToken)
    {
    }

    public static function fromString(string $passwordResetToken): self
    {
        return new self($passwordResetToken);
    }

    public function __toString(): string
    {
        return $this->passwordResetToken;
    }
}
