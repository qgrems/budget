<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\ReadModels\Projections;

use App\UserManagement\Domain\Events\UserSignedUpEvent;
use App\UserManagement\Domain\Events\UserDeletedEvent;
use App\UserManagement\Domain\Events\UserFirstnameUpdatedEvent;
use App\UserManagement\Domain\Events\UserLastnameUpdatedEvent;
use App\UserManagement\Domain\Events\UserPasswordResetEvent;
use App\UserManagement\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserManagement\Domain\Events\UserPasswordUpdatedEvent;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\MailerInterface;
use App\UserManagement\ReadModels\Projections\UserProjection;
use App\UserManagement\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserProjectionTest extends TestCase
{
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private MailerInterface&MockObject $mailer;
    private UserProjection $userProjection;

    protected function setUp(): void
    {
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->userProjection = new UserProjection($this->userViewRepository, $this->mailer);
    }

    public function testHandleUserCreatedEvent(): void
    {
        $event = new UserSignedUpEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'john.doe@example.com', 'John', 'Doe', 'password123', true, ['ROLE_USER']);

        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UserView $view) use ($event) {
                return $view->getUuid() === $event->getAggregateId()
                    && $view->getCreatedAt() == $event->occurredOn()
                    && $view->getUpdatedAt() == \DateTime::createFromImmutable($event->occurredOn())
                    && $view->getEmail() === $event->getEmail()
                    && $view->getFirstName() === $event->getFirstName()
                    && $view->getLastName() === $event->getLastName()
                    && $view->getPassword() === $event->getPassword()
                    && $view->isConsentGiven() === $event->isConsentGiven()
                    && $view->getRoles() === $event->getRoles();
            }));

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserFirstnameUpdatedEvent(): void
    {
        $event = new UserFirstnameUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'John');
        $userView = new UserView();
        $userView->setUuid($event->getAggregateId());

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn($userView);

        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLastnameUpdatedEvent(): void
    {
        $event = new UserLastnameUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Doe');
        $userView = new UserView();
        $userView->setUuid($event->getAggregateId());

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn($userView);

        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordResetEvent(): void
    {
        $event = new UserPasswordResetEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'newpassword123');
        $userView = new UserView();
        $userView->setUuid($event->getAggregateId());

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn($userView);

        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordResetRequestedEvent(): void
    {
        $event = new UserPasswordResetRequestedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'reset-token-123', new \DateTimeImmutable('+1 hour'));
        $userView = new UserView();
        $userView->setUuid($event->getAggregateId());

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn($userView);

        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->mailer->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with($userView, 'reset-token-123');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordUpdatedEvent(): void
    {
        $event = new UserPasswordUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'oldpassword123', 'newpassword123');
        $userView = new UserView();
        $userView->setUuid($event->getAggregateId());

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn($userView);

        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserDeletedEvent(): void
    {
        $event = new UserDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b');
        $userView = new UserView();
        $userView->setUuid($event->getAggregateId());

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn($userView);

        $this->userViewRepository->expects($this->once())
            ->method('delete')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserFirstnameUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserFirstnameUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'John');

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLastnameUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserLastnameUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Doe');

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordResetEventWithUserThatDoesNotExist(): void
    {
        $event = new UserPasswordResetEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'newpassword123');

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordResetRequestedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserPasswordResetRequestedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'reset-token-123', new \DateTimeImmutable('+1 hour'));

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserPasswordUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'oldpassword123', 'newpassword123');

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserDeletedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b');

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId()])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }
}
