<?php

declare(strict_types=1);

namespace App\UserContext\Domain\ValueObjects;

use Assert\Assert;

final readonly class UserLanguagePreference
{
    private function __construct(protected string $language)
    {
        Assert::that($language)
            ->notBlank('Language preference should not be blank.')
            ->minLength(2, 'The language preference must be at least 2 characters long.')
            ->maxLength(35, 'The language preference cannot be longer than 35 characters.')
        ;
    }

    public static function fromString(string $language): self
    {
        return new self($language);
    }

    public function __toString(): string
    {
        return $this->language;
    }
}
