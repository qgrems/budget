<?php

declare(strict_types=1);

namespace App\UserContext\ReadModels\Projections;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\UserContext\Domain\Events\UserReplayedEvent;
use App\UserContext\Domain\Events\UserRewoundEvent;
use App\UserContext\Domain\Events\UserSignedUpEvent;
use App\UserContext\Domain\Events\UserDeletedEvent;
use App\UserContext\Domain\Events\UserFirstnameUpdatedEvent;
use App\UserContext\Domain\Events\UserLastnameUpdatedEvent;
use App\UserContext\Domain\Events\UserPasswordResetEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserContext\Domain\Events\UserPasswordUpdatedEvent;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Outbound\MailerInterface;
use App\UserContext\ReadModels\Views\UserView;

final readonly class UserProjection
{
    public function __construct(
        private UserViewRepositoryInterface $userViewRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(EventInterface $event): void
    {
        match(true) {
            $event instanceof UserSignedUpEvent => $this->handleUserSignedUpEvent($event),
            $event instanceof UserFirstnameUpdatedEvent => $this->handleUserFirstnameUpdatedEvent($event),
            $event instanceof UserLastnameUpdatedEvent => $this->handleUserLastnameUpdatedEvent($event),
            $event instanceof UserPasswordResetEvent => $this->handleUserPasswordResetEvent($event),
            $event instanceof UserPasswordResetRequestedEvent => $this->handleUserPasswordResetRequestedEvent($event),
            $event instanceof UserPasswordUpdatedEvent => $this->handleUserPasswordUpdatedEvent($event),
            $event instanceof UserDeletedEvent => $this->handleUserDeletedEvent($event),
            $event instanceof UserReplayedEvent => $this->handleUserReplayedEvent($event),
            $event instanceof UserRewoundEvent => $this->handleUserRewoundEvent($event),
            default => null,
        };
    }

    private function handleUserSignedUpEvent(UserSignedUpEvent $event): void
    {
        $this->userViewRepository->save(UserView::fromUserSignedUpEvent($event));
    }

    private function handleUserFirstnameUpdatedEvent(UserFirstnameUpdatedEvent $userFirstnameUpdatedEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userFirstnameUpdatedEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userFirstnameUpdatedEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserLastnameUpdatedEvent(UserLastnameUpdatedEvent $userLastnameUpdatedEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userLastnameUpdatedEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userLastnameUpdatedEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserPasswordResetEvent(UserPasswordResetEvent $userPasswordResetEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userPasswordResetEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userPasswordResetEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserPasswordResetRequestedEvent(
        UserPasswordResetRequestedEvent $userPasswordResetRequestedEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userPasswordResetRequestedEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userPasswordResetRequestedEvent);
        $this->userViewRepository->save($userView);
        $this->mailer->sendPasswordResetEmail($userView, $userPasswordResetRequestedEvent->passwordResetToken);
    }

    private function handleUserPasswordUpdatedEvent(UserPasswordUpdatedEvent $userPasswordUpdatedEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userPasswordUpdatedEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userPasswordUpdatedEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserDeletedEvent(UserDeletedEvent $userDeletedEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userDeletedEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $this->userViewRepository->delete($userView);
    }

    private function handleUserReplayedEvent(UserReplayedEvent $userReplayedEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userReplayedEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userReplayedEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserRewoundEvent(UserRewoundEvent $userRewoundEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userRewoundEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userRewoundEvent);
        $this->userViewRepository->save($userView);
    }
}
