<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

use App\Libraries\Anonymii\Ports\AnonymiiUserDomainEventInterface;
use App\Libraries\FluxCapacitor\Ports\DomainEventInterface;

interface UserDomainEventInterface extends DomainEventInterface, AnonymiiUserDomainEventInterface
{
    public function toArray(): array;

    public static function fromArray(array $data): self;
}
