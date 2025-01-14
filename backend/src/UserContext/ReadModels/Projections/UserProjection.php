<?php

declare(strict_types=1);

namespace App\UserContext\ReadModels\Projections;

use App\UserContext\Domain\Events\UserDeletedDomainEvent;
use App\UserContext\Domain\Events\UserFirstnameUpdatedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameUpdatedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordUpdatedDomainEvent;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use App\UserContext\Domain\Events\UserRewoundDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;
use App\UserContext\Domain\Ports\Inbound\KeyManagementRepositoryInterface;
use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\MailerInterface;
use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use App\UserContext\ReadModels\Views\UserView;

final readonly class UserProjection
{
    public function __construct(
        private UserViewRepositoryInterface      $userViewRepository,
        private MailerInterface                  $mailer,
        private KeyManagementRepositoryInterface $keyManagementRepository,
        private EventEncryptorInterface          $eventEncryptor,
        private RefreshTokenManagerInterface     $refreshTokenManager,
    ) {
    }

    public function __invoke(UserDomainEventInterface $event): void
    {
        $encryptionKey = $this->keyManagementRepository->getKey($event->aggregateId);

        if (!$encryptionKey) {
            return;
        }

        $event = $this->eventEncryptor->decrypt($event, $event->aggregateId);

        match(true) {
            $event instanceof UserSignedUpDomainEvent => $this->handleUserSignedUpDomainEvent($event),
            $event instanceof UserFirstnameUpdatedDomainEvent => $this->handleUserFirstnameUpdatedDomainEvent($event),
            $event instanceof UserLastnameUpdatedDomainEvent => $this->handleUserLastnameUpdatedDomainEvent($event),
            $event instanceof UserPasswordResetDomainEvent => $this->handleUserPasswordResetEvent($event),
            $event instanceof UserPasswordResetRequestedDomainEvent => $this->handleUserPasswordResetRequestedDomainEvent($event),
            $event instanceof UserPasswordUpdatedDomainEvent => $this->handleUserPasswordUpdatedDomainEvent($event),
            $event instanceof UserDeletedDomainEvent => $this->handleUserDeletedDomainEvent($event),
            $event instanceof UserReplayedDomainEvent => $this->handleUserReplayedDomainEvent($event),
            $event instanceof UserRewoundDomainEvent => $this->handleUserRewoundDomainEvent($event),
            default => null,
        };
    }

    private function handleUserSignedUpDomainEvent(UserSignedUpDomainEvent $userSignedUpDomainEvent): void
    {
        $this->userViewRepository->save(UserView::fromUserSignedUpDomainEvent($userSignedUpDomainEvent));
    }

    private function handleUserFirstnameUpdatedDomainEvent(
        UserFirstnameUpdatedDomainEvent $userFirstnameUpdatedDomainEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userFirstnameUpdatedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userFirstnameUpdatedDomainEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserLastnameUpdatedDomainEvent(
        UserLastnameUpdatedDomainEvent $userLastnameUpdatedDomainEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userLastnameUpdatedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userLastnameUpdatedDomainEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserPasswordResetEvent(UserPasswordResetDomainEvent $userPasswordResetDomainEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userPasswordResetDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userPasswordResetDomainEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserPasswordResetRequestedDomainEvent(
        UserPasswordResetRequestedDomainEvent $userPasswordResetRequestedDomainEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy(
            ['uuid' => $userPasswordResetRequestedDomainEvent->aggregateId],
        );

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userPasswordResetRequestedDomainEvent);
        $this->userViewRepository->save($userView);
        $this->mailer->sendPasswordResetEmail($userView, $userPasswordResetRequestedDomainEvent->passwordResetToken);
    }

    private function handleUserPasswordUpdatedDomainEvent(
        UserPasswordUpdatedDomainEvent $userPasswordUpdatedDomainEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userPasswordUpdatedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userPasswordUpdatedDomainEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserDeletedDomainEvent(UserDeletedDomainEvent $userDeletedDomainEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userDeletedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $this->userViewRepository->delete($userView);
        $this->keyManagementRepository->deleteKey($userDeletedDomainEvent->aggregateId);
        $this->refreshTokenManager->deleteAll($userView->getEmail());
    }

    private function handleUserReplayedDomainEvent(UserReplayedDomainEvent $userReplayedDomainEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userReplayedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userReplayedDomainEvent);
        $this->userViewRepository->save($userView);
    }

    private function handleUserRewoundDomainEvent(UserRewoundDomainEvent $userRewoundDomainEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userRewoundDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userRewoundDomainEvent);
        $this->userViewRepository->save($userView);
    }
}
