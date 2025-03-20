<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Ports;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

interface UserDomainEventInterface extends DomainEventInterface, AbstractUserDomainEventInterface
{
    public function toArray(): array;

    public static function fromArray(array $data): self;
}
