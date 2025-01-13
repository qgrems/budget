<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\UpdateABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeContext\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\Presentation\HTTP\DTOs\UpdateABudgetEnvelopeTargetedAmountInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}/update-target-budget', name: 'app_budget_envelope_update_targeted_amount', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class UpdateABudgetEnvelopeTargetedAmountController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] UpdateABudgetEnvelopeTargetedAmountInput $updateABudgetEnvelopeTargetedAmountInput,
        string $uuid,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new UpdateABudgetEnvelopeTargetedAmountCommand(
                BudgetEnvelopeTargetedAmount::fromString(
                    $updateABudgetEnvelopeTargetedAmountInput->targetedAmount,
                    $updateABudgetEnvelopeTargetedAmountInput->currentAmount,
                ),
                BudgetEnvelopeId::fromString($uuid),
                BudgetEnvelopeUserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
