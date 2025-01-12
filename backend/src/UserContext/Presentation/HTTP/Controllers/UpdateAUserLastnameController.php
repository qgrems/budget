<?php

declare(strict_types=1);

namespace App\UserContext\Presentation\HTTP\Controllers;

use App\UserContext\Application\Commands\UpdateAUserLastnameCommand;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Presentation\HTTP\DTOs\UpdateAUserLastnameInput;
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
