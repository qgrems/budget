<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Aggregates;

use App\BudgetPlanContext\Domain\Events\BudgetPlanCurrencyChangedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanAlreadyExistsException;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanIsNotOwnedByUserException;
use App\BudgetPlanContext\Domain\Exceptions\InvalidBudgetPlanOperationException;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanCurrency;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryAmount;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryName;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncome;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncomeCategory;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanNeed;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanNeedCategory;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanSaving;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanSavingCategory;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanWant;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanWantCategory;
use App\Libraries\FluxCapacitor\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\Ports\DomainEventInterface;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\Libraries\FluxCapacitor\Traits\DomainEventsCapabilityTrait;
use App\SharedContext\Domain\Ports\Outbound\TranslatorInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;

final class BudgetPlan implements AggregateRootInterface
{
    use DomainEventsCapabilityTrait;

    private BudgetPlanId $budgetPlanId;
    private BudgetPlanUserId $userId;
    private BudgetPlanCurrency $currency;
    private array $incomes;
    private array $needs;
    private array $wants;
    private array $savings;
    private \DateTimeImmutable $date;
    private int $aggregateVersion = 0;
    private bool $isDeleted = false;
    private \DateTime $updatedAt;

    private function __construct()
    {
    }

    public static function fromEvents(\Generator $events, EventClassMapInterface $eventClassMap): self
    {
        $aggregate = new self();

        /** @var array{stream_version: int, event_name: string, payload: string} $event */
        foreach ($events as $event) {
            $aggregate->apply(
            /** @var DomainEventInterface $eventClassMap->getEventPathByClassName($event['event_name']) */
            $eventClassMap->getEventPathByClassName($event['event_name'])::fromArray(
                    json_decode($event['payload'], true)
                )
            );
            $aggregate->aggregateVersion = $event['stream_version'];
        }

        return $aggregate;
    }

    public static function create(
        BudgetPlanId $budgetPlanId,
        \DateTimeImmutable $date,
        array $incomes,
        BudgetPlanUserId $userId,
        UserLanguagePreference $userLanguagePreference,
        BudgetPlanCurrency $currency,
        BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
        UuidGeneratorInterface $uuidGenerator,
        TranslatorInterface $translator,
    ): self {
        if ($budgetPlanViewRepository->findOneBy([
            'uuid' => (string) $budgetPlanId,
            'user_uuid' => (string) $userId,
            'date' => $date->format(\DateTime::ATOM),
        ])) {
            throw new BudgetPlanAlreadyExistsException();
        }

        $budgetPlanGeneratedDomainEvent = new BudgetPlanGeneratedDomainEvent(
            (string) $budgetPlanId,
            $date->format(\DateTimeInterface::ATOM),
            (string) $currency,
            array_map(fn(BudgetPlanIncome $income) => $income->toArray(), $incomes),
            array_map(fn(BudgetPlanNeed $need) => $need->toArray(), self::generateFakeNeeds($incomes, (string) $userLanguagePreference, $uuidGenerator, $translator)),
            array_map(fn(BudgetPlanWant $want) => $want->toArray(), self::generateFakeWants($incomes, (string) $userLanguagePreference, $uuidGenerator, $translator)),
            array_map(fn(BudgetPlanSaving $saving) => $saving->toArray(), self::generateFakeSavings($incomes, (string) $userLanguagePreference, $uuidGenerator, $translator)),
            (string) $userId,
        );

        $aggregate = new self();
        $aggregate->apply($budgetPlanGeneratedDomainEvent);
        $aggregate->raiseDomainEvents($budgetPlanGeneratedDomainEvent);

        return $aggregate;
    }

    public static function createWithOneThatAlreadyExists(
        BudgetPlanId $budgetPlanId,
        BudgetPlanId $budgetPlanIdThatAlreadyExists,
        \DateTimeImmutable $date,
        BudgetPlanUserId $userId,
        BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
        UuidGeneratorInterface $uuidGenerator,
    ): self {
        if ($budgetPlanViewRepository->findOneBy([
            'uuid' => (string) $budgetPlanId,
            'user_uuid' => (string) $userId,
            'date' => $date->format(\DateTime::ATOM),
        ])) {
            throw new BudgetPlanAlreadyExistsException();
        }

        $existingBudgetPlan = $budgetPlanViewRepository->findOnePlanWithEntriesBy([
            'uuid' => (string) $budgetPlanIdThatAlreadyExists,
            'is_deleted' => false,
        ]);

        if (empty($existingBudgetPlan)) {
            throw new \RuntimeException('The budget plan that already exists was not found.');
        }

        $currentDate = new \DateTimeImmutable();
        $budgetPlanId = (string) $budgetPlanId;

        $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent = new BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
            $budgetPlanId,
            $date->format(\DateTimeInterface::ATOM),
            $existingBudgetPlan['budgetPlan']->currency,
            array_map(fn(BudgetPlanIncome $income) => $income->toArray(),
                self::generateIncomesFromABudgetPlanThatAlreadyExists(
                    $existingBudgetPlan['incomes'],
                    $uuidGenerator,
                    $currentDate,
                    $budgetPlanId,
                ),
            ),
            array_map(fn(BudgetPlanNeed $need) => $need->toArray(),
                self::generateNeedsFromABudgetPlanThatAlreadyExists(
                    $existingBudgetPlan['needs'],
                    $uuidGenerator,
                    $currentDate,
                    $budgetPlanId,
                ),
            ),
            array_map(fn(BudgetPlanWant $want) => $want->toArray(),
                self::generateWantsFromABudgetPlanThatAlreadyExists(
                    $existingBudgetPlan['wants'],
                    $uuidGenerator,
                    $currentDate,
                    $budgetPlanId,
                ),
            ),
            array_map(fn(BudgetPlanSaving $saving) => $saving->toArray(),
                self::generateSavingsFromABudgetPlanThatAlreadyExists(
                    $existingBudgetPlan['savings'],
                    $uuidGenerator,
                    $currentDate,
                    $budgetPlanId,
                ),
            ),
            (string) $userId,
        );

        $aggregate = new self();
        $aggregate->apply($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent);
        $aggregate->raiseDomainEvents($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent);

        return $aggregate;
    }

    public function changeCurrency(
        BudgetPlanCurrency $budgetPlanCurrency,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetPlanCurrencyChangedDomainEvent = new BudgetPlanCurrencyChangedDomainEvent(
            (string) $this->budgetPlanId,
            (string) $this->userId,
            (string) $budgetPlanCurrency,
        );

        $this->apply($budgetPlanCurrencyChangedDomainEvent);
        $this->raiseDomainEvents($budgetPlanCurrencyChangedDomainEvent);
    }

    public function addIncome(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $incomeId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanIncomeCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetPlanIncomeAddedDomainEvent = new BudgetPlanIncomeAddedDomainEvent(
            (string) $budgetPlanId,
            (string) $incomeId,
            (string) $userId,
            (string) $amount,
            (string) $name,
            (string) $category,
        );
        $this->apply($budgetPlanIncomeAddedDomainEvent);
        $this->raiseDomainEvents($budgetPlanIncomeAddedDomainEvent);
    }

    public function addWant(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $wantId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanWantCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetPlanWantAddedDomainEvent = new BudgetPlanWantAddedDomainEvent(
            (string) $budgetPlanId,
            (string) $wantId,
            (string) $userId,
            (string) $amount,
            (string) $name,
            (string) $category,
        );
        $this->apply($budgetPlanWantAddedDomainEvent);
        $this->raiseDomainEvents($budgetPlanWantAddedDomainEvent);
    }

    public function addNeed(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $needId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanNeedCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetPlanNeedAddedDomainEvent = new BudgetPlanNeedAddedDomainEvent(
            (string) $budgetPlanId,
            (string) $needId,
            (string) $userId,
            (string) $amount,
            (string) $name,
            (string) $category,
        );
        $this->apply($budgetPlanNeedAddedDomainEvent);
        $this->raiseDomainEvents($budgetPlanNeedAddedDomainEvent);
    }

    public function addSaving(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $savingId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanSavingCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetPlanSavingAddedDomainEvent = new BudgetPlanSavingAddedDomainEvent(
            (string) $budgetPlanId,
            (string) $savingId,
            (string) $userId,
            (string) $amount,
            (string) $name,
            (string) $category,
        );
        $this->apply($budgetPlanSavingAddedDomainEvent);
        $this->raiseDomainEvents($budgetPlanSavingAddedDomainEvent);
    }

    public function adjustAWant(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $wantId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanWantCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetPlanWantAdjustedDomainEvent = new BudgetPlanWantAdjustedDomainEvent(
            (string) $budgetPlanId,
            (string) $wantId,
            (string) $userId,
            (string) $amount,
            (string) $name,
            (string) $category,
        );
        $this->apply($budgetPlanWantAdjustedDomainEvent);
        $this->raiseDomainEvents($budgetPlanWantAdjustedDomainEvent);
    }

    public function adjustANeed(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $needId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanNeedCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetPlanNeedAdjustedDomainEvent = new BudgetPlanNeedAdjustedDomainEvent(
            (string) $budgetPlanId,
            (string) $needId,
            (string) $userId,
            (string) $amount,
            (string) $name,
            (string) $category,
        );
        $this->apply($budgetPlanNeedAdjustedDomainEvent);
        $this->raiseDomainEvents($budgetPlanNeedAdjustedDomainEvent);
    }

    public function adjustASaving(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $savingId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanSavingCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetPlanSavingAdjustedDomainEvent = new BudgetPlanSavingAdjustedDomainEvent(
            (string) $budgetPlanId,
            (string) $savingId,
            (string) $userId,
            (string) $amount,
            (string) $name,
            (string) $category,
        );
        $this->apply($budgetPlanSavingAdjustedDomainEvent);
        $this->raiseDomainEvents($budgetPlanSavingAdjustedDomainEvent);
    }

    public function adjustAnIncome(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $incomeId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanIncomeCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetPlanIncomeAdjustedDomainEvent = new BudgetPlanIncomeAdjustedDomainEvent(
            (string) $budgetPlanId,
            (string) $incomeId,
            (string) $userId,
            (string) $amount,
            (string) $name,
            (string) $category,
        );
        $this->apply($budgetPlanIncomeAdjustedDomainEvent);
        $this->raiseDomainEvents($budgetPlanIncomeAdjustedDomainEvent);
    }

    public function removeAnIncome(
        BudgetPlanEntryId $incomeId,
        BudgetPlanUserId $userId,
    ): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetPlanIncomeRemovedDomainEvent = new BudgetPlanIncomeRemovedDomainEvent(
            (string) $this->budgetPlanId,
            (string) $incomeId,
            (string) $userId,
        );
        $this->apply($budgetPlanIncomeRemovedDomainEvent);
        $this->raiseDomainEvents($budgetPlanIncomeRemovedDomainEvent);
    }

    public function removeASaving(
        BudgetPlanEntryId $savingId,
        BudgetPlanUserId $userId,
    ): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetPlanSavingRemovedDomainEvent = new BudgetPlanSavingRemovedDomainEvent(
            (string) $this->budgetPlanId,
            (string) $savingId,
            (string) $userId,
        );
        $this->apply($budgetPlanSavingRemovedDomainEvent);
        $this->raiseDomainEvents($budgetPlanSavingRemovedDomainEvent);
    }

    public function removeAWant(
        BudgetPlanEntryId $wantId,
        BudgetPlanUserId $userId,
    ): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetPlanWantRemovedDomainEvent = new BudgetPlanWantRemovedDomainEvent(
            (string) $this->budgetPlanId,
            (string) $wantId,
            (string) $userId,
        );
        $this->apply($budgetPlanWantRemovedDomainEvent);
        $this->raiseDomainEvents($budgetPlanWantRemovedDomainEvent);
    }

    public function removeANeed(
        BudgetPlanEntryId $needId,
        BudgetPlanUserId $userId,
    ): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetPlanNeedRemovedDomainEvent = new BudgetPlanNeedRemovedDomainEvent(
            (string) $this->budgetPlanId,
            (string) $needId,
            (string) $userId,
        );
        $this->apply($budgetPlanNeedRemovedDomainEvent);
        $this->raiseDomainEvents($budgetPlanNeedRemovedDomainEvent);
    }

    public function remove(BudgetPlanUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetPlanRemovedDomainEvent = new BudgetPlanRemovedDomainEvent(
            (string) $this->budgetPlanId,
            (string) $this->userId,
            true,
        );

        $this->apply($budgetPlanRemovedDomainEvent);
        $this->raiseDomainEvents($budgetPlanRemovedDomainEvent);
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->applyBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanRemovedDomainEvent::class => $this->applyBudgetPlanRemovedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanCurrencyChangedDomainEvent::class => $this->applyBudgetPlanCurrencyChangedDomainEvent($event),
            BudgetPlanIncomeAddedDomainEvent::class => $this->applyBudgetPlanIncomeAddedDomainEvent($event),
            BudgetPlanWantAddedDomainEvent::class => $this->applyBudgetPlanWantAddedDomainEvent($event),
            BudgetPlanNeedAddedDomainEvent::class => $this->applyBudgetPlanNeedAddedDomainEvent($event),
            BudgetPlanSavingAddedDomainEvent::class => $this->applyBudgetPlanSavingAddedDomainEvent($event),
            BudgetPlanIncomeAdjustedDomainEvent::class => $this->applyBudgetPlanIncomeAdjustedDomainEvent($event),
            BudgetPlanWantAdjustedDomainEvent::class => $this->applyBudgetPlanWantAdjustedDomainEvent($event),
            BudgetPlanNeedAdjustedDomainEvent::class => $this->applyBudgetPlanNeedAdjustedDomainEvent($event),
            BudgetPlanSavingAdjustedDomainEvent::class => $this->applyBudgetPlanSavingAdjustedDomainEvent($event),
            BudgetPlanIncomeRemovedDomainEvent::class => $this->applyBudgetPlanIncomeRemovedDomainEvent($event),
            BudgetPlanWantRemovedDomainEvent::class => $this->applyBudgetPlanWantRemovedDomainEvent($event),
            BudgetPlanNeedRemovedDomainEvent::class => $this->applyBudgetPlanNeedRemovedDomainEvent($event),
            BudgetPlanSavingRemovedDomainEvent::class => $this->applyBudgetPlanSavingRemovedDomainEvent($event),
            default => throw new \RuntimeException('Unknown event type'),
        };
    }

    private function applyBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        $this->budgetPlanId = BudgetPlanId::fromString($event->aggregateId);
        $this->userId = BudgetPlanUserId::fromString($event->userId);
        $this->date = new \DateTimeImmutable($event->date);
        $this->currency = BudgetPlanCurrency::fromString($event->currency);
        $this->incomes = array_map(fn(array $income) => BudgetPlanIncome::fromArray($income), $event->incomes);
        $this->needs = array_map(fn(array $income) => BudgetPlanNeed::fromArray($income), $event->needs);
        $this->wants = array_map(fn(array $income) => BudgetPlanWant::fromArray($income), $event->wants);
        $this->savings = array_map(fn(array $income) => BudgetPlanSaving::fromArray($income), $event->savings);
        $this->isDeleted = false;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event
    ): void {
        $this->budgetPlanId = BudgetPlanId::fromString($event->aggregateId);
        $this->userId = BudgetPlanUserId::fromString($event->userId);
        $this->date = new \DateTimeImmutable($event->date);
        $this->currency = BudgetPlanCurrency::fromString($event->currency);
        $this->incomes = array_map(fn(array $income) => BudgetPlanIncome::fromArray($income), $event->incomes);
        $this->needs = array_map(fn(array $income) => BudgetPlanNeed::fromArray($income), $event->needs);
        $this->wants = array_map(fn(array $income) => BudgetPlanWant::fromArray($income), $event->wants);
        $this->savings = array_map(fn(array $income) => BudgetPlanSaving::fromArray($income), $event->savings);
        $this->isDeleted = false;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanCurrencyChangedDomainEvent(
        BudgetPlanCurrencyChangedDomainEvent $budgetPlanCurrencyChangedDomainEvent,
    ): void {
        $this->currency = BudgetPlanCurrency::fromString(
            $budgetPlanCurrencyChangedDomainEvent->currency,
        );
        $this->updatedAt = \DateTime::createFromImmutable($budgetPlanCurrencyChangedDomainEvent->occurredOn);
    }

    private function applyBudgetPlanRemovedDomainEvent(
        BudgetPlanRemovedDomainEvent $budgetPlanRemovedDomainEvent,
    ): void {
        $this->isDeleted = $budgetPlanRemovedDomainEvent->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($budgetPlanRemovedDomainEvent->occurredOn);
    }

    private function applyBudgetPlanIncomeAddedDomainEvent(
        BudgetPlanIncomeAddedDomainEvent $event,
    ): void {
        $this->incomes[] = BudgetPlanIncome::fromArray([
            'uuid' => $event->uuid,
            'incomeName' => $event->name,
            'category' => $event->category,
            'amount' => $event->amount,
        ]);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanWantAddedDomainEvent(
        BudgetPlanWantAddedDomainEvent $event,
    ): void {
        $this->wants[] = BudgetPlanWant::fromArray([
            'uuid' => $event->uuid,
            'wantName' => $event->name,
            'category' => $event->category,
            'amount' => $event->amount,
        ]);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanNeedAddedDomainEvent(
        BudgetPlanNeedAddedDomainEvent $event,
    ): void {
        $this->needs[] = BudgetPlanNeed::fromArray([
            'uuid' => $event->uuid,
            'needName' => $event->name,
            'category' => $event->category,
            'amount' => $event->amount,
        ]);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanSavingAddedDomainEvent(
        BudgetPlanSavingAddedDomainEvent $event,
    ): void {
        $this->savings[] = BudgetPlanSaving::fromArray([
            'uuid' => $event->uuid,
            'savingName' => $event->name,
            'category' => $event->category,
            'amount' => $event->amount,
        ]);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanIncomeAdjustedDomainEvent(
        BudgetPlanIncomeAdjustedDomainEvent $event,
    ): void {
        $this->incomes = array_map(function(BudgetPlanIncome $income) use ($event) {
            if ($income->getUuid() === $event->uuid) {
                return BudgetPlanIncome::fromArray([
                    'uuid' => $event->uuid,
                    'incomeName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]);
            }
            return $income;
        }, $this->incomes);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanWantAdjustedDomainEvent(
        BudgetPlanWantAdjustedDomainEvent $event,
    ): void {
        $this->wants = array_map(function(BudgetPlanWant $want) use ($event) {
            if ($want->getUuid() === $event->uuid) {
                return BudgetPlanWant::fromArray([
                    'uuid' => $event->uuid,
                    'wantName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]);
            }
            return $want;
        }, $this->wants);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanNeedAdjustedDomainEvent(
        BudgetPlanNeedAdjustedDomainEvent $event,
    ): void {
        $this->needs = array_map(function(BudgetPlanNeed $need) use ($event) {
            if ($need->getUuid() === $event->uuid) {
                return BudgetPlanNeed::fromArray([
                    'uuid' => $event->uuid,
                    'needName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]);
            }
            return $need;
        }, $this->needs);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanSavingAdjustedDomainEvent(
        BudgetPlanSavingAdjustedDomainEvent $event,
    ): void {
        $this->savings = array_map(function(BudgetPlanSaving $saving) use ($event) {
            if ($saving->getUuid() === $event->uuid) {
                return BudgetPlanSaving::fromArray([
                    'uuid' => $event->uuid,
                    'savingName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]);
            }
            return $saving;
        }, $this->savings);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanIncomeRemovedDomainEvent(
        BudgetPlanIncomeRemovedDomainEvent $event,
    ): void {
        $this->incomes = array_filter($this->incomes, fn(BudgetPlanIncome $income) => $income->getUuid() !== $event->uuid);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanWantRemovedDomainEvent(
        BudgetPlanWantRemovedDomainEvent $event,
    ): void {
        $this->wants = array_filter($this->wants, fn(BudgetPlanWant $want) => $want->getUuid() !== $event->uuid);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanNeedRemovedDomainEvent(
        BudgetPlanNeedRemovedDomainEvent $event,
    ): void {
        $this->needs = array_filter($this->needs, fn(BudgetPlanNeed $need) => $need->getUuid() !== $event->uuid);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanSavingRemovedDomainEvent(
        BudgetPlanSavingRemovedDomainEvent $event,
    ): void {
        $this->savings = array_filter($this->savings, fn(BudgetPlanSaving $saving) => $saving->getUuid() !== $event->uuid);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private static function generateFakeNeeds(
        array $incomes,
        string $userPreferredLanguage,
        UuidGeneratorInterface $uuidGenerator,
        TranslatorInterface $translator
    ): array {
        $needsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.50;

        return [
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => $translator->trans(
                    id:'needs.rent',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'rent',
                'amount' => (string) ($needsAmount * 0.40),
            ]),
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => $translator->trans(
                    id:'needs.utilities',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'utilities',
                'amount' => (string) ($needsAmount * 0.20),
            ]),
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => $translator->trans(
                    id:'needs.food',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'food',
                'amount' => (string) ($needsAmount * 0.40),
            ]),
        ];
    }

    private static function generateFakeWants(
        array $incomes,
        string $userPreferredLanguage,
        UuidGeneratorInterface $uuidGenerator,
        TranslatorInterface $translator,
    ): array {
        $wantsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.30;

        return [
            BudgetPlanWant::fromArray(
                ['uuid' => $uuidGenerator->generate(),
                    'wantName' => $translator->trans(
                        id:'wants.entertainment',
                        domain: 'messages',
                        locale: $userPreferredLanguage,
                    ),
                    'category' => 'entertainment',
                    'amount' => (string) ($wantsAmount * 0.50),
                ]),
            BudgetPlanWant::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'wantName' => $translator->trans(
                    id:'wants.dining-out',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'dining-out',
                'amount' => (string) ($wantsAmount * 0.50),
            ]),
        ];
    }

    private static function generateFakeSavings(
        array $incomes,
        string $userPreferredLanguage,
        UuidGeneratorInterface $uuidGenerator,
        TranslatorInterface $translator,
    ): array {
        $savingsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.20;

        return [
            BudgetPlanSaving::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'savingName' => $translator->trans(
                    id:'savings.emergency-fund',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'emergency-fund',
                'amount' => (string) ($savingsAmount * 0.50),
            ]),
            BudgetPlanSaving::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'savingName' => $translator->trans(
                    id:'savings.retirement',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'retirement',
                'amount' => (string) ($savingsAmount * 0.50),
            ]),
        ];
    }

    private static function generateIncomesFromABudgetPlanThatAlreadyExists(
        array $existingIncomes,
        UuidGeneratorInterface $uuidGenerator,
        \DateTimeImmutable $currentDate,
        string $budgetPlanId,
    ): array {
        return array_map(function(BudgetPlanIncomeEntryViewInterface $income) use ($uuidGenerator, $currentDate, $budgetPlanId) {
            $incomeArray = $income->toArray();
            $incomeArray['uuid'] = $uuidGenerator->generate();
            $incomeArray['budget_plan_uuid'] = $budgetPlanId;
            $incomeArray['created_at'] = $currentDate->format(\DateTimeInterface::ATOM);
            $incomeArray['updated_at'] = $currentDate->format(\DateTimeInterface::ATOM);
            $incomeArray['amount'] = $income->incomeAmount;
            $incomeArray['category'] = $income->category;
            $incomeArray['income_name'] = $income->incomeName;
            return BudgetPlanIncome::fromArray($incomeArray);
        }, $existingIncomes);
    }

    private static function generateNeedsFromABudgetPlanThatAlreadyExists(
        array $existingNeeds,
        UuidGeneratorInterface $uuidGenerator,
        \DateTimeImmutable $currentDate,
        string $budgetPlanId,
    ): array {
        return array_map(function(BudgetPlanNeedEntryViewInterface $need) use ($uuidGenerator, $currentDate, $budgetPlanId) {
            $needArray = $need->toArray();
            $needArray['uuid'] = $uuidGenerator->generate();
            $needArray['budget_plan_uuid'] = $budgetPlanId;
            $needArray['created_at'] = $currentDate->format(\DateTimeInterface::ATOM);
            $needArray['updated_at'] = $currentDate->format(\DateTimeInterface::ATOM);
            $needArray['amount'] = $need->needAmount;
            $needArray['category'] = $need->category;
            $needArray['need_name'] = $need->needName;
            return BudgetPlanNeed::fromArray($needArray);
        }, $existingNeeds);
    }

    private static function generateSavingsFromABudgetPlanThatAlreadyExists(
        array $existingSavings,
        UuidGeneratorInterface $uuidGenerator,
        \DateTimeImmutable $currentDate,
        string $budgetPlanId,
    ): array {
        return array_map(function(BudgetPlanSavingEntryViewInterface $saving) use ($uuidGenerator, $currentDate, $budgetPlanId) {
            $savingArray = $saving->toArray();
            $savingArray['uuid'] = $uuidGenerator->generate();
            $savingArray['budget_plan_uuid'] = $budgetPlanId;
            $savingArray['created_at'] = $currentDate->format(\DateTimeInterface::ATOM);
            $savingArray['updated_at'] = $currentDate->format(\DateTimeInterface::ATOM);
            $savingArray['amount'] = $saving->savingAmount;
            $savingArray['category'] = $saving->category;
            $savingArray['saving_name'] = $saving->savingName;
            return BudgetPlanSaving::fromArray($savingArray);
        }, $existingSavings);
    }

    private static function generateWantsFromABudgetPlanThatAlreadyExists(
        array $existingWants,
        UuidGeneratorInterface $uuidGenerator,
        \DateTimeImmutable $currentDate,
        string $budgetPlanId,
    ): array {
        return array_map(function(BudgetPlanWantEntryViewInterface $want) use ($uuidGenerator, $currentDate, $budgetPlanId) {
            $wantArray = $want->toArray();
            $wantArray['uuid'] = $uuidGenerator->generate();
            $wantArray['budget_plan_uuid'] = $budgetPlanId;
            $wantArray['created_at'] = $currentDate->format(\DateTimeInterface::ATOM);
            $wantArray['updated_at'] = $currentDate->format(\DateTimeInterface::ATOM);
            $wantArray['amount'] = $want->wantAmount;
            $wantArray['want_name'] = $want->wantName;
            $wantArray['category'] = $want->category;
            return BudgetPlanWant::fromArray($wantArray);
        }, $existingWants);
    }

    private function assertOwnership(BudgetPlanUserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw BudgetPlanIsNotOwnedByUserException::isNotOwnedByUser();
        }
    }

    private function assertNotDeleted(): void
    {
        if ($this->isDeleted) {
            throw InvalidBudgetPlanOperationException::operationOnDeletedEnvelope();
        }
    }
}
