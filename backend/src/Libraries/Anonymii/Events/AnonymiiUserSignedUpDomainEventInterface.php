<?php

declare(strict_types=1);

namespace App\Libraries\Anonymii\Events;

interface AnonymiiUserSignedUpDomainEventInterface
{
    public function toArray(): array;

    public static function fromArray(array $data): self;
}
