<?php

namespace App\UserContext\Domain\Services;

use App\UserContext\Domain\Ports\Inbound\PasswordResetTokenGeneratorInterface;

final readonly class PasswordResetTokenGenerator implements PasswordResetTokenGeneratorInterface
{
    #[\Override]
    public function generate(): string
    {
        return \bin2hex(\random_bytes(32));
    }
}
