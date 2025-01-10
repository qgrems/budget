<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\ReadModels\Views;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_envelope_view')]
#[ORM\Index(name: 'idx_budget_envelope_view_user_uuid', columns: ['user_uuid'])]
#[ORM\Index(name: 'idx_budget_envelope_view_uuid', columns: ['uuid'])]
final class BudgetEnvelopeView implements BudgetEnvelopeViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\Column(name: 'current_amount', type: 'string', length: 13)]
    private string $currentAmount;

    #[ORM\Column(name: 'targeted_amount', type: 'string', length: 13)]
    private string $targetedAmount;

    #[ORM\Column(name: 'name', type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private string $userUuid;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private bool $isDeleted;

    public function __construct(
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    #[\Override]
    public static function fromRepository(array $budgetEnvelope): self
    {
        return new self()
            ->setCurrentAmount($budgetEnvelope['current_amount'])
            ->setTargetedAmount($budgetEnvelope['targeted_amount'])
            ->setName($budgetEnvelope['name'])
            ->setIsDeleted((bool) $budgetEnvelope['is_deleted'])
            ->setCreatedAt(new \DateTimeImmutable($budgetEnvelope['created_at']))
            ->setUpdatedAt(new \DateTime($budgetEnvelope['updated_at']))
            ->setTargetedAmount($budgetEnvelope['targeted_amount'])
            ->setUuid($budgetEnvelope['uuid'])
            ->setUserUuid($budgetEnvelope['user_uuid'])
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
    public function getUuid(): string
    {
        return $this->uuid;
    }

    #[\Override]
    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

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

    #[\Override]
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    #[\Override]
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[\Override]
    public function getTargetedAmount(): string
    {
        return $this->targetedAmount;
    }

    #[\Override]
    public function setTargetedAmount(string $targetedAmount): self
    {
        $this->targetedAmount = $targetedAmount;

        return $this;
    }

    #[\Override]
    public function getCurrentAmount(): string
    {
        return $this->currentAmount;
    }

    #[\Override]
    public function setCurrentAmount(string $currentAmount): self
    {
        $this->currentAmount = $currentAmount;

        return $this;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function setName(string $name): self
    {
        $this->name = $name;

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

    #[\Override]
    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    #[\Override]
    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'currentAmount' => $this->currentAmount,
            'targetedAmount' => $this->targetedAmount,
            'name' => $this->name,
        ];
    }
}
