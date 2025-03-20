<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Ports;

interface EventEncryptorInterface
{
    public function encrypt(AbstractUserDomainEventInterface $event, string $userId): AbstractUserDomainEventInterface;

    public function decrypt(AbstractUserDomainEventInterface $event, string $userId): AbstractUserDomainEventInterface;
}
