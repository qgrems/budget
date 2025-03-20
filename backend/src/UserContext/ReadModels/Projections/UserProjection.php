<?php

declare(strict_types=1);

namespace App\UserContext\ReadModels\Projections;

use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;
use App\UserContext\Domain\Events\UserDeletedDomainEvent;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use App\UserContext\Domain\Events\UserRewoundDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\MailerInterface;
use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use App\UserContext\Infrastructure\Events\Notifications\UserDeletedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserFirstnameChangedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserLanguagePreferenceChangedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserLastnameChangedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserPasswordChangedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserPasswordResetNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserPasswordResetRequestedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserReplayedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserRewoundNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserSignedUpNotificationEvent;
use App\UserContext\ReadModels\Views\UserView;

final readonly class UserProjection
{
    public function __construct(
        private UserViewRepositoryInterface $userViewRepository,
        private MailerInterface $mailer,
        private KeyManagementRepositoryInterface $keyManagementRepository,
        private EventEncryptorInterface $eventEncryptor,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(UserDomainEventInterface $event): void
    {
        $encryptionKey = $this->keyManagementRepository->getKey($event->aggregateId);

        if (!$encryptionKey) {
            return;
        }

        $event = $this->eventEncryptor->decrypt($event, $event->aggregateId);

        match($event::class) {
            UserSignedUpDomainEvent::class => $this->handleUserSignedUpDomainEvent($event),
            UserFirstnameChangedDomainEvent::class => $this->handleUserFirstnameChangedDomainEvent($event),
            UserLastnameChangedDomainEvent::class => $this->handleUserLastnameChangedDomainEvent($event),
            UserLanguagePreferenceChangedDomainEvent::class => $this->handleUserLanguagePreferenceChangedDomainEvent($event),
            UserPasswordResetDomainEvent::class => $this->handleUserPasswordResetEvent($event),
            UserPasswordResetRequestedDomainEvent::class => $this->handleUserPasswordResetRequestedDomainEvent($event),
            UserPasswordChangedDomainEvent::class => $this->handleUserPasswordChangedDomainEvent($event),
            UserDeletedDomainEvent::class => $this->handleUserDeletedDomainEvent($event),
            UserReplayedDomainEvent::class => $this->handleUserReplayedDomainEvent($event),
            UserRewoundDomainEvent::class => $this->handleUserRewoundDomainEvent($event),
            default => null,
        };
    }

    private function handleUserSignedUpDomainEvent(UserSignedUpDomainEvent $userSignedUpDomainEvent): void
    {
        $this->userViewRepository->save(UserView::fromUserSignedUpDomainEvent($userSignedUpDomainEvent));
        try {
            $this->publisher->publishNotificationEvents([
                UserSignedUpNotificationEvent::fromDomainEvent(
                    $userSignedUpDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserFirstnameChangedDomainEvent(
        UserFirstnameChangedDomainEvent $userFirstnameChangedDomainEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userFirstnameChangedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userFirstnameChangedDomainEvent);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserFirstnameChangedNotificationEvent::fromDomainEvent(
                    $userFirstnameChangedDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserLanguagePreferenceChangedDomainEvent(
        UserLanguagePreferenceChangedDomainEvent $userLanguagePreferenceChangedDomainEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy([
            'uuid' => $userLanguagePreferenceChangedDomainEvent->aggregateId
        ]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userLanguagePreferenceChangedDomainEvent);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserLanguagePreferenceChangedNotificationEvent::fromDomainEvent(
                    $userLanguagePreferenceChangedDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserLastnameChangedDomainEvent(
        UserLastnameChangedDomainEvent $userLastnameChangedDomainEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userLastnameChangedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userLastnameChangedDomainEvent);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserLastnameChangedNotificationEvent::fromDomainEvent(
                    $userLastnameChangedDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserPasswordResetEvent(UserPasswordResetDomainEvent $userPasswordResetDomainEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userPasswordResetDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userPasswordResetDomainEvent);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserPasswordResetNotificationEvent::fromDomainEvent(
                    $userPasswordResetDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
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
        try {
            $this->publisher->publishNotificationEvents([
                UserPasswordResetRequestedNotificationEvent::fromDomainEvent(
                    $userPasswordResetRequestedDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserPasswordChangedDomainEvent(
        UserPasswordChangedDomainEvent $userPasswordChangedDomainEvent,
    ): void {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userPasswordChangedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userPasswordChangedDomainEvent);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserPasswordChangedNotificationEvent::fromDomainEvent(
                    $userPasswordChangedDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
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
        try {
            $this->publisher->publishNotificationEvents([
                UserDeletedNotificationEvent::fromDomainEvent(
                    $userDeletedDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserReplayedDomainEvent(UserReplayedDomainEvent $userReplayedDomainEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userReplayedDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userReplayedDomainEvent);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserReplayedNotificationEvent::fromDomainEvent(
                    $userReplayedDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserRewoundDomainEvent(UserRewoundDomainEvent $userRewoundDomainEvent): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $userRewoundDomainEvent->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($userRewoundDomainEvent);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserRewoundNotificationEvent::fromDomainEvent(
                    $userRewoundDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }
}
