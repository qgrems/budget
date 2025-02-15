<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;

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
