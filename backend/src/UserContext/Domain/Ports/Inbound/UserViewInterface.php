<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\UserContext\Domain\Events\UserSignedUpEvent;

interface UserViewInterface
{
    public static function fromRepository(array $user): self;

    public static function fromUserSignedUpEvent(UserSignedUpEvent $userSignedUpEvent): self;

    public function fromEvents(\Generator $events): void;

    public function fromEvent(EventInterface $event): void;

    public function getPassword(): ?string;

    public function getUuid(): string;

    public function getEmail(): string;

    public function getRoles(): array;

    public function eraseCredentials(): void;

    public function getUserIdentifier(): string;

    public function jsonSerialize(): array;
}
