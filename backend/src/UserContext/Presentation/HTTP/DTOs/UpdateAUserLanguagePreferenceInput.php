<?php

declare(strict_types=1);

namespace App\UserContext\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAUserLanguagePreferenceInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Locale(message: 'users.languagePreferenceInvalid')]
        private(set) string $languagePreference,
    ) {
    }
}
