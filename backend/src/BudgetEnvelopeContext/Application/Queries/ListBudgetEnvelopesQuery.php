<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Queries;

use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\QueryInterface;

final readonly class ListBudgetEnvelopesQuery implements QueryInterface
{
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        private ?array $orderBy = null,
        private ?int $limit = null,
        private ?int $offset = null,
    ) {
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeUserId(): BudgetEnvelopeUserId
    {
        return BudgetEnvelopeUserId::fromString($this->budgetEnvelopeUserId);
    }

    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }
}
