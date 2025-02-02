<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeContext\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\Presentation\HTTP\DTOs\ChangeABudgetEnvelopeTargetedAmountInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}/change-targeted-amount', name: 'app_budget_envelope_change_targeted_amount', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class ChangeABudgetEnvelopeTargetedAmountController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] ChangeABudgetEnvelopeTargetedAmountInput $changeABudgetEnvelopeTargetedAmountInput,
        string                                                        $uuid,
        #[CurrentUser] SharedUserInterface                            $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeABudgetEnvelopeTargetedAmountCommand(
                BudgetEnvelopeTargetedAmount::fromString(
                    $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                    $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
                ),
                BudgetEnvelopeId::fromString($uuid),
                BudgetEnvelopeUserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
