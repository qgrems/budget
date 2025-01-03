<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LogoutAUserInput
{
    public function __construct(
        #[Assert\NotBlank]
        public string $refreshToken,
    ) {
    }
}
