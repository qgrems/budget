<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Ports\Inbound;

interface UserViewRepositoryInterface
{
    public function save(UserViewInterface $user): void;

    public function delete(UserViewInterface $user): void;

    public function findOneBy(array $criteria, ?array $orderBy = null): ?UserViewInterface;
}
