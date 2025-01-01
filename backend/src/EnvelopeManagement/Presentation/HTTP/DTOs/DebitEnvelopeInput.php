<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DebitEnvelopeInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type(type: 'string')]
        #[Assert\Length(
            min: 1,
            max: 13,
            minMessage: 'envelopes.debitMoneyMinLength',
            maxMessage: 'envelopes.debitMoneyMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^\d+(\.\d{2})?$/',
            message: 'envelopes.debitMoneyInvalid'
        )]
        public string $debitMoney,
    ) {
    }

    public function getDebitMoney(): string
    {
        return $this->debitMoney;
    }
}
