<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

interface UserSignedUpDomainEventInterface extends UserDomainEventInterface
{
    public function toArray(): array;

    public static function fromArray(array $data): self;
}
