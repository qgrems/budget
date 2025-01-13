<?php

declare(strict_types=1);

namespace App\UserContext\Domain\ValueObjects;

use Assert\Assert;

final readonly class UserPassword
{
    private function __construct(protected string $password)
    {
        Assert::that($password)
            ->notBlank('UserPassword should not be blank.')
            ->minLength(8, 'The password must be at least 8 characters long.')
        ;
    }

    public static function fromString(string $password): self
    {
        return new self($password);
    }

    public function __toString(): string
    {
        return $this->password;
    }
}
