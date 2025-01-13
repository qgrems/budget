<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\CommandInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;

final readonly class RewindABudgetEnvelopeFromEventsCommand implements CommandInterface
{
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;
    private string $desiredDateTime;

    public function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        \DateTimeImmutable $desiredDateTime,
    ) {
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
        $this->desiredDateTime = $desiredDateTime->format(\DateTimeInterface::ATOM);
    }

    public function getBudgetEnvelopeUserId(): BudgetEnvelopeUserId
    {
        return BudgetEnvelopeUserId::fromString($this->budgetEnvelopeUserId);
    }

    public function getBudgetEnvelopeId(): BudgetEnvelopeId
    {
        return BudgetEnvelopeId::fromString($this->budgetEnvelopeId);
    }

    public function getDesiredDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->desiredDateTime);
    }
}
