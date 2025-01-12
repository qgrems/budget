<?php

namespace App\UserContext\Domain\Ports\Inbound;

interface PasswordResetTokenGeneratorInterface
{
    public function generate(): string;
}
