<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedEvent;

interface BudgetEnvelopeHistoryViewInterface
{
    public static function fromRepository(array $budgetEnvelopeHistory): self;

    public function jsonSerialize(): array;

    public static function fromBudgetEnvelopeCreditedEvent(BudgetEnvelopeCreditedEvent $budgetEnvelopeCreditedEvent, string $userUuid): self;

    public static function fromBudgetEnvelopeDebitedEvent(BudgetEnvelopeDebitedEvent $event, string $userUuid): self;
}
