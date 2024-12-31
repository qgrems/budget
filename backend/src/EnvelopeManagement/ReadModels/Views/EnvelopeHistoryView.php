<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\ReadModels\Views;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'envelope_history_view')]
#[ORM\Index(name: 'idx_envelope_history_view_user_uuid', columns: ['user_uuid'])]
final class EnvelopeHistoryView implements EnvelopeHistoryViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'aggregate_id', type: 'string', length: 36, unique: false)]
    private string $aggregateId;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'monetary_amount', type: 'string', length: 13)]
    private string $monetaryAmount;

    #[ORM\Column(name: 'transaction_type', type: 'string', length: 6)]
    private string $transactionType;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private string $userUuid;

    public function __construct(
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[\Override]
    public static function create(
        string $aggregateId,
        \DateTimeImmutable $createdAt,
        string $monetaryAmount,
        string $transactionType,
        string $userUuid
    ): self {
        return new self()
            ->setAggregateId($aggregateId)
            ->setCreatedAt($createdAt)
            ->setMonetaryAmount($monetaryAmount)
            ->setTransactionType($transactionType)
            ->setUserUuid($userUuid)
            ;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    #[\Override]
    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    #[\Override]
    public function setAggregateId(string $aggregateId): self
    {
        $this->aggregateId = $aggregateId;

        return $this;
    }

    #[\Override]
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[\Override]
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTransactionType(): string
    {
        return $this->transactionType;
    }

    public function setTransactionType(string $transactionType): self
    {
        $this->transactionType = $transactionType;

        return $this;
    }


    #[\Override]
    public function getMonetaryAmount(): string
    {
        return $this->monetaryAmount;
    }

    #[\Override]
    public function setMonetaryAmount(string $monetaryAmount): self
    {
        $this->monetaryAmount = $monetaryAmount;

        return $this;
    }

    #[\Override]
    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    #[\Override]
    public function setUserUuid(string $userUuid): self
    {
        $this->userUuid = $userUuid;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'aggregate_id' => $this->aggregateId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'monetary_amount' => $this->monetaryAmount,
            'transaction_type' => $this->transactionType,
            'user_uuid' => $this->userUuid,
        ];
    }
}
