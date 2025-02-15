<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserPassword;

final readonly class ChangeAUserPasswordCommand implements CommandInterface
{
    private string $userOldPassword;
    private string $userNewPassword;
    private string $userId;

    public function __construct(
        UserPassword $userOldPassword,
        UserPassword $userNewPassword,
        UserId $userId,
    ) {
        $this->userOldPassword = (string) $userOldPassword;
        $this->userNewPassword = (string) $userNewPassword;
        $this->userId = (string) $userId;
    }

    public function getUserOldPassword(): UserPassword
    {
        return UserPassword::fromString($this->userOldPassword);
    }

    public function getUserNewPassword(): UserPassword
    {
        return UserPassword::fromString($this->userNewPassword);
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }
}
