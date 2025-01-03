<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SignUpAUserInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[Assert\Regex(
            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        )]
        public string $uuid,
        #[Assert\NotBlank]
        #[Assert\Email(message: 'users.emailInvalid')]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 8,
            max: 50,
            minMessage: 'users.passwordMinLength',
            maxMessage: 'users.passwordMaxLength',
        )]
        #[Assert\PasswordStrength]
        public string $password,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'users.firstnameMinLength',
            maxMessage: 'users.firstnameMaxLength',
        )]
        public string $firstname,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'users.lastnameMinLength',
            maxMessage: 'users.lastnameMaxLength',
        )]
        public string $lastname,
        #[Assert\NotNull]
        #[Assert\IsTrue(message: 'users.consentNotGiven')]
        #[Assert\Type(type: 'bool')]
        public bool $consentGiven,
    ) {
    }
}
