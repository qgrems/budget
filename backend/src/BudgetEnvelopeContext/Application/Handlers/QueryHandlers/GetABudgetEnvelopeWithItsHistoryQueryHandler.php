<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeContext\Application\Queries\GetABudgetEnvelopeWithItsHistoryQuery;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;

final readonly class GetABudgetEnvelopeWithItsHistoryQueryHandler
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    /**
     * @throws BudgetEnvelopeNotFoundException
     */
    public function __invoke(GetABudgetEnvelopeWithItsHistoryQuery $getABudgetEnvelopeWithItsHistoryQuery): array
    {
        $budgetEnvelope = $this->budgetEnvelopeViewRepository->findOneEnvelopeWithHistoryBy([
            'uuid' => (string) $getABudgetEnvelopeWithItsHistoryQuery->getBudgetEnvelopeId(),
            'user_uuid' => (string) $getABudgetEnvelopeWithItsHistoryQuery->getBudgetEnvelopeUserId(),
            'is_deleted' => false,
        ]);

        if ([] === $budgetEnvelope) {
            throw new BudgetEnvelopeNotFoundException();
        }

        return $budgetEnvelope;
    }
}
