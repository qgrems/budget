<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Commands;

use App\UserManagement\Domain\Ports\Inbound\CommandInterface;
use App\UserManagement\Domain\ValueObjects\UserEmail;

final readonly class RequestAUserPasswordResetCommand implements CommandInterface
{
    private string $email;

    public function __construct(
        UserEmail $email,
    ) {
        $this->email = (string) $email;
    }

    public function getUserEmail(): UserEmail
    {
        return UserEmail::fromString($this->email);
    }
}
