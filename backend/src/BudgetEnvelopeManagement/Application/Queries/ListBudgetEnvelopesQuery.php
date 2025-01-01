<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Queries;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\QueryInterface;

final readonly class ListBudgetEnvelopesQuery implements QueryInterface
{
    public function __construct(
        private string $userUuid,
        private ?array $orderBy = null,
        private ?int $limit = null,
        private ?int $offset = null,
    ) {
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
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
