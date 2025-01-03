<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\Controllers;

use App\UserManagement\Application\Commands\UpdateAUserFirstnameCommand;
use App\UserManagement\Domain\Ports\Inbound\UserViewInterface;
use App\UserManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\UserManagement\Domain\ValueObjects\UserFirstname;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Presentation\HTTP\DTOs\UpdateAUserFirstnameInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/firstname', name: 'app_user_edit_firstname', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class UpdateAUserFirstnameController
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
        #[MapRequestPayload] UpdateAUserFirstnameInput $updateAUserFirstnameInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new UpdateAUserFirstnameCommand(
                UserId::fromString($currentUser->getUuid()),
                UserFirstname::fromString($updateAUserFirstnameInput->firstname),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
