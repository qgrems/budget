<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\CreditABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\Presentation\HTTP\DTOs\CreditABudgetEnvelopeInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}/credit', name: 'app_budget_envelope_credit', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class CreditABudgetEnvelopeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreditABudgetEnvelopeInput $creditABudgetEnvelopeInput,
        string $uuid,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new CreditABudgetEnvelopeCommand(
                BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
                BudgetEnvelopeId::fromString($uuid),
                BudgetEnvelopeUserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
