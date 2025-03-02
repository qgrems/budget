<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\ValueObjects;

use Assert\Assert;

final readonly class UserLanguagePreference
{
    private function __construct(protected string $language)
    {
        Assert::that($language)
            ->notBlank('Language preference should not be blank.')
            ->length(2, 'The language preference must be 2 characters long.')
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
