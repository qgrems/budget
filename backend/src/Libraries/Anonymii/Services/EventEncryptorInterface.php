<?php

declare(strict_types=1);

namespace App\Libraries\Anonymii\Services;

use App\Libraries\Anonymii\Events\AnonymiiUserDomainEventInterface;

interface EventEncryptorInterface
{
    public function encrypt(AnonymiiUserDomainEventInterface $event, string $userId): AnonymiiUserDomainEventInterface;

    public function decrypt(AnonymiiUserDomainEventInterface $event, string $userId): AnonymiiUserDomainEventInterface;
}
