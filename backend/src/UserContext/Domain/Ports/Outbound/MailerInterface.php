<?php

namespace App\UserContext\Domain\Ports\Outbound;

use App\UserContext\Domain\Ports\Inbound\UserViewInterface;

interface MailerInterface
{
    public function sendPasswordResetEmail(UserViewInterface $user, string $token): void;
}
