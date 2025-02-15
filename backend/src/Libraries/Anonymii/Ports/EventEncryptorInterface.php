<?php

declare(strict_types=1);

namespace App\Libraries\Anonymii\Ports;

interface EventEncryptorInterface
{
    public function encrypt(AnonymiiUserDomainEventInterface $event, string $userId): AnonymiiUserDomainEventInterface;

    public function decrypt(AnonymiiUserDomainEventInterface $event, string $userId): AnonymiiUserDomainEventInterface;
}
