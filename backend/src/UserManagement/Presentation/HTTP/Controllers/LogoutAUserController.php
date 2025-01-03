<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\Controllers;

use App\UserManagement\Application\Commands\LogoutAUserCommand;
use App\UserManagement\Domain\Ports\Inbound\UserViewInterface;
use App\UserManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\UserManagement\Presentation\HTTP\DTOs\LogoutAUserInput;
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
