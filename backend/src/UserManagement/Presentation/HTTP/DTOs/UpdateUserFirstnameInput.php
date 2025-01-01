<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserFirstnameInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'users.firstnameMinLength',
            maxMessage: 'users.firstnameMaxLength',
        )]
        public string $firstname,
    ) {
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }
}
