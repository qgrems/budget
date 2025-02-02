<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserPasswordChangedDomainEvent;
use PHPUnit\Framework\TestCase;

class UserPasswordChangedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserPasswordChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'oldpassword123',
            'newpassword123',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('oldpassword123', $array['oldPassword']);
        $this->assertEquals('newpassword123', $array['newPassword']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'requestId' => '8f636cef-6a4d-40f1-a9cf-4e64f67ce7c0',
            'oldPassword' => 'oldpassword123',
            'newPassword' => 'newpassword123',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserPasswordChangedDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['requestId'], $event->requestId);
        $this->assertEquals($data['oldPassword'], $event->oldPassword);
        $this->assertEquals($data['newPassword'], $event->newPassword);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
