<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\Controllers;

use App\UserManagement\Application\Commands\UpdateAUserLastnameCommand;
use App\UserManagement\Domain\Ports\Inbound\UserViewInterface;
use App\UserManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\UserLastname;
use App\UserManagement\Presentation\HTTP\DTOs\UpdateAUserLastnameInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/lastname', name: 'app_user_edit_lastname', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class UpdateAUserLastnameController
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
        #[MapRequestPayload] UpdateAUserLastnameInput $updateAUserLastnameInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new UpdateAUserLastnameCommand(
                UserId::fromString($currentUser->getUuid()),
                UserLastname::fromString($updateAUserLastnameInput->lastname),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
