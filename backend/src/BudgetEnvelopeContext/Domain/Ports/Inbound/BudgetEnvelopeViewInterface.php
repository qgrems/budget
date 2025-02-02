<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;

interface BudgetEnvelopeViewInterface
{
    public static function fromRepository(array $budgetEnvelope): self;

    public static function fromBudgetEnvelopeAddedDomainEvent(
        BudgetEnvelopeAddedDomainEvent $budgetEnvelopeAddedDomainEvent,
    ): self;

    public static function fromEvents(\Generator $events): self;

    public function fromEvent(DomainEventInterface $event): void;

    public function jsonSerialize(): array;
}
