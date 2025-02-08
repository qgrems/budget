<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\Adapters;

use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final readonly class PublisherAdapter implements PublisherInterface
{
    private const int DELIVERY_MODE_PERSISTENT = 2;

    public function __construct(private AMQPStreamConnection $amqpsStreamConnection)
    {
    }

    #[\Override]
    public function publishDomainEvents(array $events): void
    {
        $channel = $this->amqpsStreamConnection->channel();
        $channel->exchange_declare('domain_events', 'fanout', false, true, false);

        foreach ($events as $event) {
            $messageBody = json_encode($event->toArray(), JSON_THROW_ON_ERROR);
            $headers = ['type' => $event::class];
            $message = new AMQPMessage($messageBody, [
                'content_type' => 'application/json',
                'delivery_mode' => self::DELIVERY_MODE_PERSISTENT,
            ]);
            $message->set('application_headers', new AMQPTable($headers));
            $channel->basic_publish($message, 'domain_events');
        }

        $channel->close();
    }

    #[\Override]
    public function publishNotificationEvents(array $events): void
    {
        $channel = $this->amqpsStreamConnection->channel();
        $channel->exchange_declare('notification_events', 'fanout', false, true, false);

        foreach ($events as $event) {
            $messageBody = json_encode($event->toArray(), JSON_THROW_ON_ERROR);
            $headers = ['type' => $event::class];
            $message = new AMQPMessage($messageBody, [
                'content_type' => 'application/json',
                'delivery_mode' => self::DELIVERY_MODE_PERSISTENT,
            ]);
            $message->set('application_headers', new AMQPTable($headers));
            $channel->basic_publish($message, 'notification_events');
        }

        $channel->close();
    }
}
