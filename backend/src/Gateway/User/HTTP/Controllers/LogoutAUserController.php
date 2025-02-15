<?php

declare(strict_types=1);

namespace App\Gateway\User\HTTP\Controllers;

use App\Gateway\User\HTTP\DTOs\LogoutAUserInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Application\Commands\LogoutAUserCommand;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/logout', name: 'app_user_logout', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class LogoutAUserController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserViewInterface $currentUser,
        #[MapRequestPayload] LogoutAUserInput $logoutAUserInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new LogoutAUserCommand(
                $logoutAUserInput->refreshToken,
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
