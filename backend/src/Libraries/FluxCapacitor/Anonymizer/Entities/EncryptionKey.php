<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'encryption_keys')]
#[ORM\Index(name: 'idx_encryption_keys_user_id', columns: ['user_id'])]
final class EncryptionKey
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id {
        get {
            return $this->id;
        }
        set {
            $this->id = $value;
        }
    }

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId {
        get {
            return $this->userId;
        }
        set {
            $this->userId = $value;
        }
    }

    #[ORM\Column(name: 'encryption_key', type: 'text')]
    private string $encryptionKey {
        get {
            return $this->encryptionKey;
        }
        set {
            $this->encryptionKey = $value;
        }
    }

    #[ORM\Column(name:'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt {
        get {
            return $this->createdAt;
        }
        set {
            $this->createdAt = $value;
        }
    }
}
