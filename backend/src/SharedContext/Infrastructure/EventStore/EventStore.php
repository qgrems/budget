<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\EventStore;

use App\SharedContext\Domain\Exceptions\PublishEventsException;
use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;
use App\SharedContext\Domain\Services\RequestIdProvider;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class EventStore implements EventStoreInterface
{
    public function __construct(
        private Connection $connection,
        private PublisherInterface $publisher,
        private RequestIdProvider $requestIdProvider,
    ) {
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function load(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): \Generator
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('aggregate_id', 'type', 'payload', 'occurred_on', 'request_id', 'user_id')
            ->from('event_store')
            ->where('aggregate_id = :id')
            ->setParameter('id', $uuid)
            ->orderBy('occurred_on', 'ASC');

        if (null !== $desiredDateTime) {
            $queryBuilder->andWhere('occurred_on <= :desiredDateTime')
                ->setParameter('desiredDateTime', $desiredDateTime->format('Y-m-d H:i:s'));
        }

        yield from $queryBuilder->executeQuery()->iterateAssociative();
    }

    public function loadByDomainEvents(
        string $uuid,
        array $domainEventClasses,
        ?\DateTimeImmutable $desiredDateTime = null
    ): \Generator {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('aggregate_id', 'type', 'payload', 'occurred_on', 'request_id', 'user_id')
            ->from('event_store')
            ->where('aggregate_id = :id')
            ->setParameter('id', $uuid)
            ->andWhere('type IN (:domainEventClasses)')
            ->setParameter('domainEventClasses', $domainEventClasses, ArrayParameterType::STRING)
            ->orderBy('occurred_on', 'ASC');

        if (null !== $desiredDateTime) {
            $queryBuilder->andWhere('occurred_on <= :desiredDateTime')
                ->setParameter('desiredDateTime', $desiredDateTime->format('Y-m-d H:i:s'));
        }

        yield from $queryBuilder->executeQuery()->iterateAssociative();
    }

    #[\Override]
    public function save(array $events): void
    {
        try {
            $this->connection->beginTransaction();

            foreach ($events as $event) {
                $currentVersion = $this->getCurrentVersion($event->aggregateId);
                $this->connection->insert('event_store', [
                    'aggregate_id' => $event->aggregateId,
                    'type' => get_class($event),
                    'payload' => json_encode($event->toArray(), JSON_THROW_ON_ERROR),
                    'occurred_on' => $event->occurredOn->format('Y-m-d H:i:s'),
                    'version' => $currentVersion + 1,
                    'request_id' => $this->requestIdProvider->requestId,
                    'user_id' => $event->userId,
                ]);
            }

            $this->publisher->publishDomainEvents($events);
            $this->connection->commit();
        } catch (Exception $exception) {
            $this->connection->rollBack();
            throw new PublishEventsException();
        }
    }

    private function getCurrentVersion(string $aggregateId): int
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('MAX(version) as version')
            ->from('event_store')
            ->where('aggregate_id = :id')
            ->setParameter('id', $aggregateId);

        return (int) $queryBuilder->executeQuery()->fetchOne();
    }
}
