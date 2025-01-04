<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeManagement\Application\Queries\ListBudgetEnvelopesQuery;
use App\BudgetEnvelopeManagement\Domain\Ports\Outbound\QueryBusInterface;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs\ListBudgetEnvelopesInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes', name: 'app_budget_envelopes_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListBudgetEnvelopesController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(
        #[CurrentUser] SharedUserInterface $user,
        #[MapQueryString] ListBudgetEnvelopesInput $listBudgetEnvelopesInput = new ListBudgetEnvelopesInput(),
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new ListBudgetEnvelopesQuery(
                    BudgetEnvelopeUserId::fromString($user->getUuid()),
                    $listBudgetEnvelopesInput->orderBy,
                    $listBudgetEnvelopesInput->limit,
                    $listBudgetEnvelopesInput->offset,
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
