<?php

namespace App\BudgetEnvelopeManagement\ReadModels\Projections;

use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedEvent;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewRepositoryInterface;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeHistoryView;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeView;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeViewInterface;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final readonly class BudgetEnvelopeProjection
{
    public const string CREDIT = 'credit';
    public const string DEBIT = 'debit';

    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
        private BudgetEnvelopeHistoryViewRepositoryInterface $budgetEnvelopeHistoryViewRepository,
    ) {
    }

    public function __invoke(EventInterface $event): void
    {
        match(true) {
            $event instanceof BudgetEnvelopeCreatedEvent => $this->handleEnvelopeCreatedEvent($event),
            $event instanceof BudgetEnvelopeCreditedEvent => $this->handleEnvelopeCreditedEvent($event),
            $event instanceof BudgetEnvelopeDebitedEvent => $this->handleEnvelopeDebitedEvent($event),
            $event instanceof BudgetEnvelopeRenamedEvent => $this->handleEnvelopeNamedEvent($event),
            $event instanceof BudgetEnvelopeDeletedEvent => $this->handleEnvelopeDeletedEvent($event),
            $event instanceof BudgetEnvelopeTargetedAmountUpdatedEvent => $this->handleEnvelopeTargetedAmountUpdatedEvent($event),
            default => null,
        };
    }

    private function handleEnvelopeCreatedEvent(BudgetEnvelopeCreatedEvent $event): void
    {
        $this->budgetEnvelopeViewRepository->save(
            new BudgetEnvelopeView()
                ->setUuid($event->getAggregateId())
                ->setCreatedAt($event->occurredOn())
                ->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()))
                ->setIsDeleted(false)
                ->setTargetedAmount($event->getTargetedAmount())
                ->setCurrentAmount('0.00')
                ->setName($event->getName())
                ->setUserUuid($event->getUserId())
        );
    }

    private function handleEnvelopeCreditedEvent(BudgetEnvelopeCreditedEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->getAggregateId(), 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $budgetEnvelopeView->setCurrentAmount((string) (
            floatval($budgetEnvelopeView->getCurrentAmount()) + floatval($event->getCreditMoney())
        ));
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        $this->saveEnvelopeHistory($event, $budgetEnvelopeView);
    }

    private function handleEnvelopeDebitedEvent(BudgetEnvelopeDebitedEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->getAggregateId(), 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $budgetEnvelopeView->setCurrentAmount((string) (
            floatval($budgetEnvelopeView->getCurrentAmount()) - floatval($event->getDebitMoney())
        ));
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        $this->saveEnvelopeHistory($event, $budgetEnvelopeView);
    }

    private function handleEnvelopeNamedEvent(BudgetEnvelopeRenamedEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->getAggregateId(), 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $budgetEnvelopeView->setName($event->getName());
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleEnvelopeDeletedEvent(BudgetEnvelopeDeletedEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->getAggregateId(), 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $budgetEnvelopeView->setIsDeleted(true);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleEnvelopeTargetedAmountUpdatedEvent(BudgetEnvelopeTargetedAmountUpdatedEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->getAggregateId(), 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->setUpdatedAt(\DateTime::createFromImmutable($event->occurredOn()));
        $budgetEnvelopeView->setTargetedAmount($event->getTargetedAmount());
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    public function saveEnvelopeHistory(EventInterface $event, BudgetEnvelopeViewInterface $budgetEnvelopeView): void
    {
        $monetaryAmount = $event instanceof BudgetEnvelopeCreditedEvent ? $event->getCreditMoney() :
            ($event instanceof BudgetEnvelopeDebitedEvent ? $event->getDebitMoney() : '0.00');
        $type = $event instanceof BudgetEnvelopeCreditedEvent ? self::CREDIT :
            ($event instanceof BudgetEnvelopeDebitedEvent ? self::DEBIT : '');

        $this->budgetEnvelopeHistoryViewRepository->save(
            new BudgetEnvelopeHistoryView()
                ->setAggregateId($event->getAggregateId())
                ->setCreatedAt($event->occurredOn())
                ->setMonetaryAmount($monetaryAmount)
                ->setTransactionType($type)
                ->setUserUuid($budgetEnvelopeView->getUserUuid()),
        );
    }
}
