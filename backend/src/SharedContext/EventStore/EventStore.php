<?php

declare(strict_types=1);

namespace App\SharedContext\EventStore;

use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class EventStore implements EventStoreInterface
{
    public function __construct(private Connection $connection, private PublisherInterface $publisher)
    {
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function load(string $uuid): \Generator
    {
        yield from $this->connection->createQueryBuilder()
            ->select('aggregate_id', 'type', 'payload', 'occurred_on')
            ->from('event_store')
            ->where('aggregate_id = :id')
            ->setParameter('id', $uuid)
            ->orderBy('occurred_on', 'ASC')
            ->executeQuery()
            ->iterateAssociative();
    }

    #[\Override]
    public function save(array $events): void
    {
        foreach ($events as $event) {
            $this->connection->insert('event_store', [
                'aggregate_id' => $event->getAggregateId(),
                'type' => get_class($event),
                'payload' => json_encode($event->toArray(), JSON_THROW_ON_ERROR),
                'occurred_on' => $event->occurredOn()->format('Y-m-d H:i:s'),
            ]);
        }

        $this->publisher->publishEvents($events);
    }
}
