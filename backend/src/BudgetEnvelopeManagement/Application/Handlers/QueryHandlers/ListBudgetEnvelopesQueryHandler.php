<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeManagement\Application\Queries\ListBudgetEnvelopesQuery;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopesPaginatedInterface;

final readonly class ListBudgetEnvelopesQueryHandler
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopesRepository,
    ) {
    }

    public function __invoke(ListBudgetEnvelopesQuery $listBudgetEnvelopesQuery): BudgetEnvelopesPaginatedInterface
    {
        return $this->budgetEnvelopesRepository->findBy(
            [
                'user_uuid' => (string) $listBudgetEnvelopesQuery->getBudgetEnvelopeUserId(),
                'is_deleted' => false,
            ],
            $listBudgetEnvelopesQuery->getOrderBy(),
            $listBudgetEnvelopesQuery->getLimit(),
            $listBudgetEnvelopesQuery->getOffset(),
        );
    }
}
