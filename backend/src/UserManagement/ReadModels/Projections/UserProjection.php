<?php

declare(strict_types=1);

namespace App\UserManagement\ReadModels\Projections;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\UserManagement\Domain\Events\UserCreatedEvent;
use App\UserManagement\Domain\Events\UserDeletedEvent;
use App\UserManagement\Domain\Events\UserFirstnameUpdatedEvent;
use App\UserManagement\Domain\Events\UserLastnameUpdatedEvent;
use App\UserManagement\Domain\Events\UserPasswordResetEvent;
use App\UserManagement\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserManagement\Domain\Events\UserPasswordUpdatedEvent;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Inbound\UserViewInterface;
use App\UserManagement\Domain\Ports\Outbound\MailerInterface;
use App\UserManagement\ReadModels\Views\UserView;

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
            $event instanceof UserCreatedEvent => $this->handleUserCreatedEvent($event),
            $event instanceof UserFirstnameUpdatedEvent => $this->handleUserFirstnameUpdatedEvent($event),
            $event instanceof UserLastnameUpdatedEvent => $this->handleUserLastnameUpdatedEvent($event),
            $event instanceof UserPasswordResetEvent => $this->handleUserPasswordResetEvent($event),
            $event instanceof UserPasswordResetRequestedEvent => $this->handleUserPasswordResetRequestedEvent($event),
            $event instanceof UserPasswordUpdatedEvent => $this->handleUserPasswordUpdatedEvent($event),
            $event instanceof UserDeletedEvent => $this->handleUserDeletedEvent($event),
            default => null,
        };
    }
    
    private function handleUserCreatedEvent(UserCreatedEvent $event): void
    {
        $this->userViewRepository->save(
            new UserView()
                ->setUuid($event->getAggregateId())
                ->setCreatedAt($event->occurredOn())
                ->setEmail($event->getEmail())
                ->setFirstName($event->getFirstName())
                ->setLastName($event->getLastName())
                ->setConsentDate($event->occurredOn())
                ->setConsentGiven($event->isConsentGiven())
                ->setRoles($event->getRoles())
                ->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()))
                ->setPassword($event->getPassword())
                ->setPasswordResetToken(null)
                ->setPasswordResetTokenExpiry(null)
        );
    }
    
    private function handleUserFirstnameUpdatedEvent(UserFirstnameUpdatedEvent $event): void
    {
        $userView = $this->getUserViewByEvent($event);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $userView->setFirstName($event->getFirstName());
        $this->userViewRepository->save($userView);
    }
    
    private function handleUserLastnameUpdatedEvent(UserLastnameUpdatedEvent $event): void
    {
        $userView = $this->getUserViewByEvent($event);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $userView->setLastName($event->getLastName());
        $this->userViewRepository->save($userView);
    }
    
    private function handleUserPasswordResetEvent(UserPasswordResetEvent $event): void
    {
        $userView = $this->getUserViewByEvent($event);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $userView->setPassword($event->getPassword());
        $this->userViewRepository->save($userView);
    }
    
    private function handleUserPasswordResetRequestedEvent(UserPasswordResetRequestedEvent $event): void
    {
        $userView = $this->getUserViewByEvent($event);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $resetToken = $event->getPasswordResetToken();
        $userView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $userView->setPasswordResetToken($resetToken);
        $userView->setPasswordResetTokenExpiry($event->getPasswordResetTokenExpiry());
        $this->userViewRepository->save($userView);
        $this->mailer->sendPasswordResetEmail($userView, $resetToken);
    }
    
    private function handleUserPasswordUpdatedEvent(UserPasswordUpdatedEvent $event): void
    {
        $userView = $this->getUserViewByEvent($event);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $userView->setPassword($event->getNewPassword());
        $this->userViewRepository->save($userView);
    }

    private function handleUserDeletedEvent(UserDeletedEvent $event): void
    {
        $userView = $this->getUserViewByEvent($event);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $this->userViewRepository->delete($userView);
    }

    private function getUserViewByEvent(EventInterface $event): ?UserViewInterface
    {
        return $this->userViewRepository->findOneBy(['uuid' => $event->getAggregateId()]);
    }
}
