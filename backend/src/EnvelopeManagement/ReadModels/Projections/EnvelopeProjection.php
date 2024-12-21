<?php

namespace App\EnvelopeManagement\ReadModels\Projections;

use App\EnvelopeManagement\Domain\Events\EnvelopeCreatedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeCreditedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeDebitedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeDeletedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeNamedEvent;
use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeViewRepositoryInterface;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeView;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeViewInterface;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final readonly class EnvelopeProjection
{
    public function __construct(private EnvelopeViewRepositoryInterface $envelopeViewRepository)
    {
    }

    public function __invoke(EventInterface $event): void
    {
         match(true) {
            $event instanceof EnvelopeCreatedEvent => $this->handleEnvelopeCreatedEvent($event),
            $event instanceof EnvelopeCreditedEvent => $this->handleEnvelopeCreditedEvent($event),
            $event instanceof EnvelopeDebitedEvent => $this->handleEnvelopeDebitedEvent($event),
            $event instanceof EnvelopeNamedEvent => $this->handleEnvelopeNamedEvent($event),
            $event instanceof EnvelopeDeletedEvent => $this->handleEnvelopeDeletedEvent($event),
            default => null,
        };
    }

    private function handleEnvelopeCreatedEvent(EnvelopeCreatedEvent $event): void
    {
        $this->envelopeViewRepository->save(
            new EnvelopeView()
                ->setUuid($event->getAggregateId())
                ->setCreatedAt($event->occurredOn())
                ->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()))
                ->setIsDeleted(false)
                ->setTargetBudget($event->getTargetBudget())
                ->setCurrentBudget('0.00')
                ->setName($event->getName())
                ->setUserUuid($event->getUserId())
        );
    }

    private function handleEnvelopeCreditedEvent(EnvelopeCreditedEvent $event): void
    {
        $envelopeView = $this->getEnvelopeViewByEvent($event);

        if (!$envelopeView instanceof EnvelopeViewInterface) {
            return;
        }

        $envelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $envelopeView->setCurrentBudget((string) (
            floatval($envelopeView->getCurrentBudget()) + floatval($event->getCreditMoney())
        ));
        $this->envelopeViewRepository->save($envelopeView);
    }

    private function handleEnvelopeDebitedEvent(EnvelopeDebitedEvent $event): void
    {
        $envelopeView = $this->getEnvelopeViewByEvent($event);

        if (!$envelopeView instanceof EnvelopeViewInterface) {
            return;
        }

        $envelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $envelopeView->setCurrentBudget((string) (
            floatval($envelopeView->getCurrentBudget()) - floatval($event->getDebitMoney())
        ));
        $this->envelopeViewRepository->save($envelopeView);
    }

    private function handleEnvelopeNamedEvent(EnvelopeNamedEvent $event): void
    {
        $envelopeView = $this->getEnvelopeViewByEvent($event);

        if (!$envelopeView instanceof EnvelopeViewInterface) {
            return;
        }

        $envelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $envelopeView->setName($event->getName());
        $this->envelopeViewRepository->save($envelopeView);
    }

    private function handleEnvelopeDeletedEvent(EnvelopeDeletedEvent $event): void
    {
        $envelopeView = $this->getEnvelopeViewByEvent($event);

        if (!$envelopeView instanceof EnvelopeViewInterface) {
            return;
        }

        $envelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $envelopeView->setIsDeleted(true);
        $this->envelopeViewRepository->save($envelopeView);
    }

    public function getEnvelopeViewByEvent(EventInterface $event): ?EnvelopeViewInterface
    {
        return $this->envelopeViewRepository->findOneBy(
            ['uuid' => $event->getAggregateId(), 'is_deleted' => false],
        );
    }
}
