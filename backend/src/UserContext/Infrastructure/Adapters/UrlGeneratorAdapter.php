<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\UserContext\Domain\Ports\Outbound\UrlGeneratorInterface as CustomUrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SymfonyUrlGeneratorInterface;

final readonly class UrlGeneratorAdapter implements CustomUrlGeneratorInterface
{
    public function __construct(private SymfonyUrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[\Override]
    public function generate(string $route, array $parameters = [], int $referenceType = SymfonyUrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }
}
