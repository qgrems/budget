<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Queries;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\QueryInterface;

final readonly class GetABudgetEnvelopeWithItsHistoryQuery implements QueryInterface
{
    public function __construct(private string $budgetEnvelopeUuid, private string $userUuid)
    {
    }

    public function getEnvelopeUuid(): string
    {
        return $this->budgetEnvelopeUuid;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }
}
