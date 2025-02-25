<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\GenerateABudgetPlanWithOneThatAlreadyExistsCommand;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\Gateway\BudgetPlan\Presentation\HTTP\DTOs\GenerateABudgetPlanWithOneThatAlreadyExistsInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans-generate-with-one-that-already-exists', name: 'app_budget_plan_generate_with_one_that_already_exists', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class GenerateABudgetPlanWithOneThatAlreadyExistsController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] GenerateABudgetPlanWithOneThatAlreadyExistsInput $generateABudgetPlanWithOneThatAlreadyExistsInput,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new GenerateABudgetPlanWithOneThatAlreadyExistsCommand(
                BudgetPlanId::fromString($generateABudgetPlanWithOneThatAlreadyExistsInput->uuid),
                BudgetPlanId::fromString(
                    $generateABudgetPlanWithOneThatAlreadyExistsInput->budgetPlanUuidThatAlreadyExists,
                ),
                $generateABudgetPlanWithOneThatAlreadyExistsInput->date,
                BudgetPlanUserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
