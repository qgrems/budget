<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeManagement\Application\Queries\GetABudgetEnvelopeWithItsHistoryQuery;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;

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
            'uuid' => $getABudgetEnvelopeWithItsHistoryQuery->getEnvelopeUuid(),
            'user_uuid' => $getABudgetEnvelopeWithItsHistoryQuery->getUserUuid(),
            'is_deleted' => false,
        ]);

        if ([] === $budgetEnvelope) {
            throw new BudgetEnvelopeNotFoundException(BudgetEnvelopeNotFoundException::MESSAGE, 404);
        }

        return $budgetEnvelope;
    }
}
