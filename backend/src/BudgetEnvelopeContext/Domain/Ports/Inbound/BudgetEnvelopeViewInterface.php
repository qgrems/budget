<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;

interface BudgetEnvelopeViewInterface
{
    public static function fromRepository(array $budgetEnvelope): self;

    public static function fromBudgetEnvelopeCreatedEvent(BudgetEnvelopeCreatedEvent $budgetEnvelopeCreatedEvent): self;

    public static function fromEvents(\Generator $events): self;

    public function fromEvent(EventInterface $event): void;

    public function jsonSerialize(): array;
}
