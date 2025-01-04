<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\ReadModels\Views;

interface BudgetEnvelopeHistoryViewInterface
{
    public function getAggregateId(): string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUserUuid(): string;

    public function setAggregateId(string $aggregateId): self;

    public function setCreatedAt(\DateTimeImmutable $createdAt): self;

    public function setUserUuid(string $userUuid): self;

    public function getMonetaryAmount(): string;

    public function setMonetaryAmount(string $monetaryAmount): self;

    public function getTransactionType(): string;

    public function setTransactionType(string $transactionType): self;

    public static function fromRepository(array $budgetEnvelopeHistory): self;
}
