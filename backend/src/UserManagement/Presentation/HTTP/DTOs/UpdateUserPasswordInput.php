<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserPasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 8,
            max: 50,
            minMessage: 'users.passwordMinLength',
            maxMessage: 'users.passwordMaxLength',
        )]
        private string $oldPassword,

        #[Assert\NotBlank]
        #[Assert\Length(
            min: 8,
            max: 50,
            minMessage: 'users.passwordMinLength',
            maxMessage: 'users.passwordMaxLength',
        )]
        #[Assert\PasswordStrength]
        private string $newPassword
    ) {
    }

    public function getOldPassword(): string
    {
        return $this->oldPassword;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }
}
