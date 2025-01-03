<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAUserLastnameInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'users.lastnameMinLength',
            maxMessage: 'users.lastnameMaxLength',
        )]
        public string $lastname,
    ) {
    }
}
