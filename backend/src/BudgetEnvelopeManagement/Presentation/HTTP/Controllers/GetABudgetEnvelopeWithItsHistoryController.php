<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeManagement\Application\Queries\GetABudgetEnvelopeWithItsHistoryQuery;
use App\BudgetEnvelopeManagement\Domain\Ports\Outbound\QueryBusInterface;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}', name: 'app_budget_envelope_get_one_with_history', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class GetABudgetEnvelopeWithItsHistoryController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new GetABudgetEnvelopeWithItsHistoryQuery(
                    BudgetEnvelopeId::fromString($uuid),
                    BudgetEnvelopeUserId::fromString($user->getUuid()),
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
