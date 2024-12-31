<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Domain\Ports\Inbound;

use App\EnvelopeManagement\ReadModels\Views\EnvelopeHistoryViewInterface;

interface EnvelopeHistoryViewRepositoryInterface
{
    public function save(EnvelopeHistoryViewInterface $envelopeHistory): void;
}
