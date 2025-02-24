<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Application\Queries\ListBudgetPlansCalendarQuery;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\SharedContext\Domain\Ports\Outbound\QueryBusInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans-calendar', name: 'app_budget_plans_calendar_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListBudgetPlansCalendarController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new ListBudgetPlansCalendarQuery(
                    BudgetPlanUserId::fromString($user->getUuid()),
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
