<?php

declare(strict_types=1);

namespace App\Gateway\User\HTTP\Controllers;

use App\Gateway\User\HTTP\DTOs\ChangeAUserPasswordInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Application\Commands\ChangeAUserPasswordCommand;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\ReadModels\Views\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/change-password', name: 'app_user_change_password', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class ChangeAUserPasswordController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserView $currentUser,
        #[MapRequestPayload] ChangeAUserPasswordInput $changeAUserPasswordInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeAUserPasswordCommand(
                UserPassword::fromString($changeAUserPasswordInput->oldPassword),
                UserPassword::fromString($changeAUserPasswordInput->newPassword),
                UserId::fromString($currentUser->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
