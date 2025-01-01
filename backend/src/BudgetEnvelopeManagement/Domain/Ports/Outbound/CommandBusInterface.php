<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Ports\Outbound;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\CommandInterface;

interface CommandBusInterface
{
    public function execute(CommandInterface $command): void;
}
