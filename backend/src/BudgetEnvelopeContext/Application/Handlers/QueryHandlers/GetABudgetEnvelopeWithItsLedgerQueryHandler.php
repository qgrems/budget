<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeContext\Application\Queries\GetABudgetEnvelopeWithItsLedgerQuery;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;

final readonly class GetABudgetEnvelopeWithItsLedgerQueryHandler
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    /**
     * @throws BudgetEnvelopeNotFoundException
     */
    public function __invoke(GetABudgetEnvelopeWithItsLedgerQuery $getABudgetEnvelopeWithItsLedgerQuery): array
    {
        $budgetEnvelope = $this->budgetEnvelopeViewRepository->findOneEnvelopeWithItsLedgerBy([
            'uuid' => (string) $getABudgetEnvelopeWithItsLedgerQuery->getBudgetEnvelopeId(),
            'user_uuid' => (string) $getABudgetEnvelopeWithItsLedgerQuery->getBudgetEnvelopeUserId(),
            'is_deleted' => false,
        ]);

        if ([] === $budgetEnvelope) {
            throw new BudgetEnvelopeNotFoundException();
        }

        return $budgetEnvelope;
    }
}
