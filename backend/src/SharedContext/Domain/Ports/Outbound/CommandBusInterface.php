<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

use App\SharedContext\Domain\Ports\Inbound\CommandInterface;

interface CommandBusInterface
{
    public function execute(CommandInterface $command): void;
}
