<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Commands;

use App\UserManagement\Domain\Ports\Inbound\CommandInterface;
use App\UserManagement\Domain\ValueObjects\UserConsent;
use App\UserManagement\Domain\ValueObjects\UserEmail;
use App\UserManagement\Domain\ValueObjects\UserFirstname;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\UserLastname;
use App\UserManagement\Domain\ValueObjects\UserPassword;

final readonly class SignUpAUserCommand implements CommandInterface
{
    private string $userId;
    private string $userEmail;
    private string $userPassword;
    private string $userFirstname;
    private string $userLastname;
    private bool $userConsentGiven;

    public function __construct(
        UserId $userId,
        UserEmail $userEmail,
        UserPassword $userPassword,
        UserFirstname $userFirstname,
        UserLastname $userLastname,
        UserConsent $userConsentGiven,
    ) {
        $this->userId = (string) $userId;
        $this->userEmail = (string) $userEmail;
        $this->userPassword = (string) $userPassword;
        $this->userFirstname = (string) $userFirstname;
        $this->userLastname = (string) $userLastname;
        $this->userConsentGiven = $userConsentGiven->toBool();
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getUserEmail(): UserEmail
    {
        return UserEmail::fromString($this->userEmail);
    }

    public function getUserPassword(): UserPassword
    {
        return UserPassword::fromString($this->userPassword);
    }

    public function getUserFirstname(): UserFirstname
    {
        return UserFirstname::fromString($this->userFirstname);
    }

    public function getUserLastname(): UserLastname
    {
        return UserLastname::fromString($this->userLastname);
    }

    public function isUserConsentGiven(): UserConsent
    {
        return UserConsent::fromBool($this->userConsentGiven);
    }
}
