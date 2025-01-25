<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserLanguagePreferenceUpdatedDomainEvent;
use PHPUnit\Framework\TestCase;

class UserLanguagePreferenceUpdatedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserLanguagePreferenceUpdatedDomainEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'fr');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('fr', $array['languagePreference']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'languagePreference' => 'fr',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserLanguagePreferenceUpdatedDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['languagePreference'], $event->languagePreference);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
