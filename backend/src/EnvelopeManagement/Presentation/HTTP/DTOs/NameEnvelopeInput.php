<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class NameEnvelopeInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 1,
            max: 50,
            minMessage: 'envelopes.nameMinLength',
            maxMessage: 'envelopes.nameMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^[\p{L}\p{N} ]+$/u',
            message: 'envelopes.nameInvalid'
        )]
        public string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
