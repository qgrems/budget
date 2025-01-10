<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Ports\Inbound;

interface BudgetEnvelopeViewInterface
{
    public static function fromRepository(array $budgetEnvelope): self;

    public function getUuid(): string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTime;

    public function getCurrentAmount(): string;

    public function getTargetedAmount(): string;

    public function getName(): string;

    public function getUserUuid(): string;

    public function isDeleted(): bool;

    public function setUuid(string $uuid): self;

    public function setCreatedAt(\DateTimeImmutable $createdAt): self;

    public function setUpdatedAt(\DateTime $updatedAt): self;

    public function setCurrentAmount(string $currentAmount): self;

    public function setTargetedAmount(string $targetedAmount): self;

    public function setName(string $name): self;

    public function setUserUuid(string $userUuid): self;

    public function setIsDeleted(bool $isDeleted): self;
}
