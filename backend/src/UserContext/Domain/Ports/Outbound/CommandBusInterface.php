<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Outbound;

use App\UserContext\Domain\Ports\Inbound\CommandInterface;

interface CommandBusInterface
{
    public function execute(CommandInterface $command): void;
}
