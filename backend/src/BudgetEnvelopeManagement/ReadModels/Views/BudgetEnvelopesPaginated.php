<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\ReadModels\Views;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopesPaginatedInterface;

final class BudgetEnvelopesPaginated implements BudgetEnvelopesPaginatedInterface, \jsonSerializable
{
    /** @var array<object> */
    private iterable $budgetEnvelopes;
    private int $totalItems;

    /**
     * @param array<object> $budgetEnvelopes
     */
    public function __construct(iterable $budgetEnvelopes, int $totalItems)
    {
        $this->budgetEnvelopes = $budgetEnvelopes;
        $this->totalItems = $totalItems;
    }

    /**
     * @return array<object>
     */
    #[\Override]
    public function getEnvelopes(): iterable
    {
        return $this->budgetEnvelopes;
    }

    #[\Override]
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function jsonSerialize(): array
    {
        return [
            'envelopes' => $this->budgetEnvelopes,
            'totalItems' => $this->totalItems,
        ];
    }
}
