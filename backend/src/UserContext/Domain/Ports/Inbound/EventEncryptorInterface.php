<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

interface EventEncryptorInterface
{
    public function encrypt(UserDomainEventInterface $event, string $userId): UserDomainEventInterface;

    public function decrypt(UserDomainEventInterface $event, string $userId): UserDomainEventInterface;
}
