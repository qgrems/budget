<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Services;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface as FluxCapacitorEventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\UserContext\Domain\Events\UserDeletedDomainEvent;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use App\UserContext\Domain\Events\UserRewoundDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;

final readonly class EventClassMap implements EventClassMapInterface, FluxCapacitorEventClassMapInterface
{
    public function getClassNameByEventPath(string $eventPath): string
    {
        return match($eventPath) {
            BudgetEnvelopeAddedDomainEvent::class => 'BudgetEnvelopeAddedDomainEvent',
            BudgetEnvelopeCreditedDomainEvent::class => 'BudgetEnvelopeCreditedDomainEvent',
            BudgetEnvelopeRenamedDomainEvent::class => 'BudgetEnvelopeRenamedDomainEvent',
            BudgetEnvelopeDebitedDomainEvent::class => 'BudgetEnvelopeDebitedDomainEvent',
            BudgetEnvelopeDeletedDomainEvent::class => 'BudgetEnvelopeDeletedDomainEvent',
            BudgetEnvelopeReplayedDomainEvent::class => 'BudgetEnvelopeReplayedDomainEvent',
            BudgetEnvelopeRewoundDomainEvent::class => 'BudgetEnvelopeRewoundDomainEvent',
            BudgetEnvelopeTargetedAmountChangedDomainEvent::class => 'BudgetEnvelopeTargetedAmountChangedDomainEvent',
            UserDeletedDomainEvent::class => 'UserDeletedDomainEvent',
            UserFirstnameChangedDomainEvent::class => 'UserFirstnameChangedDomainEvent',
            UserLanguagePreferenceChangedDomainEvent::class => 'UserLanguagePreferenceChangedDomainEvent',
            UserLastnameChangedDomainEvent::class => 'UserLastnameChangedDomainEvent',
            UserPasswordChangedDomainEvent::class => 'UserPasswordChangedDomainEvent',
            UserPasswordResetDomainEvent::class => 'UserPasswordResetDomainEvent',
            UserPasswordResetRequestedDomainEvent::class => 'UserPasswordResetRequestedDomainEvent',
            UserReplayedDomainEvent::class => 'UserReplayedDomainEvent',
            UserRewoundDomainEvent::class => 'UserRewoundDomainEvent',
            UserSignedUpDomainEvent::class => 'UserSignedUpDomainEvent',
            default => $eventPath,
        };
    }

    public function getEventPathByClassName(string $eventClassName): string
    {
        return match($eventClassName) {
            'BudgetEnvelopeAddedDomainEvent' => BudgetEnvelopeAddedDomainEvent::class,
            'BudgetEnvelopeCreditedDomainEvent' => BudgetEnvelopeCreditedDomainEvent::class,
            'BudgetEnvelopeRenamedDomainEvent' => BudgetEnvelopeRenamedDomainEvent::class,
            'BudgetEnvelopeDebitedDomainEvent' => BudgetEnvelopeDebitedDomainEvent::class,
            'BudgetEnvelopeDeletedDomainEvent' => BudgetEnvelopeDeletedDomainEvent::class,
            'BudgetEnvelopeReplayedDomainEvent' => BudgetEnvelopeReplayedDomainEvent::class,
            'BudgetEnvelopeRewoundDomainEvent' => BudgetEnvelopeRewoundDomainEvent::class,
            'BudgetEnvelopeTargetedAmountChangedDomainEvent' => BudgetEnvelopeTargetedAmountChangedDomainEvent::class,
            'UserDeletedDomainEvent' => UserDeletedDomainEvent::class,
            'UserFirstnameChangedDomainEvent' => UserFirstnameChangedDomainEvent::class,
            'UserLanguagePreferenceChangedDomainEvent' => UserLanguagePreferenceChangedDomainEvent::class,
            'UserLastnameChangedDomainEvent' => UserLastnameChangedDomainEvent::class,
            'UserPasswordChangedDomainEvent' => UserPasswordChangedDomainEvent::class,
            'UserPasswordResetDomainEvent' => UserPasswordResetDomainEvent::class,
            'UserPasswordResetRequestedDomainEvent' => UserPasswordResetRequestedDomainEvent::class,
            'UserReplayedDomainEvent' => UserReplayedDomainEvent::class,
            'UserRewoundDomainEvent' => UserRewoundDomainEvent::class,
            'UserSignedUpDomainEvent' => UserSignedUpDomainEvent::class,
            default => $eventClassName,
        };
    }

    public function getClassNamesByEventsPaths(array $eventsPaths): array
    {
        return array_map(function ($eventPath) {
            return match($eventPath) {
                BudgetEnvelopeAddedDomainEvent::class => 'BudgetEnvelopeAddedDomainEvent',
                BudgetEnvelopeCreditedDomainEvent::class => 'BudgetEnvelopeCreditedDomainEvent',
                BudgetEnvelopeRenamedDomainEvent::class => 'BudgetEnvelopeRenamedDomainEvent',
                BudgetEnvelopeDebitedDomainEvent::class => 'BudgetEnvelopeDebitedDomainEvent',
                BudgetEnvelopeDeletedDomainEvent::class => 'BudgetEnvelopeDeletedDomainEvent',
                BudgetEnvelopeReplayedDomainEvent::class => 'BudgetEnvelopeReplayedDomainEvent',
                BudgetEnvelopeRewoundDomainEvent::class => 'BudgetEnvelopeRewoundDomainEvent',
                BudgetEnvelopeTargetedAmountChangedDomainEvent::class => 'BudgetEnvelopeTargetedAmountChangedDomainEvent',
                UserDeletedDomainEvent::class => 'UserDeletedDomainEvent',
                UserFirstnameChangedDomainEvent::class => 'UserFirstnameChangedDomainEvent',
                UserLanguagePreferenceChangedDomainEvent::class => 'UserLanguagePreferenceChangedDomainEvent',
                UserLastnameChangedDomainEvent::class => 'UserLastnameChangedDomainEvent',
                UserPasswordChangedDomainEvent::class => 'UserPasswordChangedDomainEvent',
                UserPasswordResetDomainEvent::class => 'UserPasswordResetDomainEvent',
                UserPasswordResetRequestedDomainEvent::class => 'UserPasswordResetRequestedDomainEvent',
                UserReplayedDomainEvent::class => 'UserReplayedDomainEvent',
                UserRewoundDomainEvent::class => 'UserRewoundDomainEvent',
                UserSignedUpDomainEvent::class => 'UserSignedUpDomainEvent',
                default => $eventPath,
            };
        }, $eventsPaths);
    }
}
