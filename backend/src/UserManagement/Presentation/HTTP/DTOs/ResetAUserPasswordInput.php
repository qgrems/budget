<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ResetAUserPasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        public string $token,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 8,
            max: 50,
            minMessage: 'users.passwordMinLength',
            maxMessage: 'users.passwordMaxLength',
        )]
        #[Assert\PasswordStrength]
        public string $newPassword,
    ) {
    }
}
