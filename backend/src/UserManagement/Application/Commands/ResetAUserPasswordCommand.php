<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Commands;

use App\UserManagement\Domain\Ports\Inbound\CommandInterface;
use App\UserManagement\Domain\ValueObjects\UserPassword;
use App\UserManagement\Domain\ValueObjects\UserPasswordResetToken;

final readonly class ResetAUserPasswordCommand implements CommandInterface
{
    private string $userPasswordResetToken;
    private string $userNewPassword;

    public function __construct(
        UserPasswordResetToken $userPasswordResetToken,
        UserPassword $userNewPassword,
    ) {
        $this->userPasswordResetToken = (string) $userPasswordResetToken;
        $this->userNewPassword = (string) $userNewPassword;
    }

    public function getUserPasswordResetToken(): UserPasswordResetToken
    {
        return UserPasswordResetToken::fromString($this->userPasswordResetToken);
    }

    public function getUserNewPassword(): UserPassword
    {
        return UserPassword::fromString($this->userNewPassword);
    }
}
