<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Commands;

use App\UserManagement\Domain\Ports\Inbound\CommandInterface;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\UserPassword;

final readonly class UpdateAUserPasswordCommand implements CommandInterface
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
