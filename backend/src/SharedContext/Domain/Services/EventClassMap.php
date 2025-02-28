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
            BudgetPlanRemovedDomainEvent::class => 'BudgetPlanRemovedDomainEvent',
            BudgetPlanWantRemovedDomainEvent::class => 'BudgetPlanWantRemovedDomainEvent',
            BudgetPlanNeedRemovedDomainEvent::class => 'BudgetPlanNeedRemovedDomainEvent',
            BudgetPlanSavingRemovedDomainEvent::class => 'BudgetPlanSavingRemovedDomainEvent',
            BudgetPlanIncomeRemovedDomainEvent::class => 'BudgetPlanIncomeRemovedDomainEvent',
            BudgetPlanGeneratedDomainEvent::class => 'BudgetPlanGeneratedDomainEvent',
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => 'BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent',
            BudgetPlanIncomeAddedDomainEvent::class => 'BudgetPlanIncomeAddedDomainEvent',
            BudgetPlanNeedAddedDomainEvent::class => 'BudgetPlanNeedAddedDomainEvent',
            BudgetPlanSavingAddedDomainEvent::class => 'BudgetPlanSavingAddedDomainEvent',
            BudgetPlanWantAddedDomainEvent::class => 'BudgetPlanWantAddedDomainEvent',
            BudgetPlanIncomeAdjustedDomainEvent::class => 'BudgetPlanIncomeAdjustedDomainEvent',
            BudgetPlanNeedAdjustedDomainEvent::class => 'BudgetPlanNeedAdjustedDomainEvent',
            BudgetPlanSavingAdjustedDomainEvent::class => 'BudgetPlanSavingAdjustedDomainEvent',
            BudgetPlanWantAdjustedDomainEvent::class => 'BudgetPlanWantAdjustedDomainEvent',
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
            'BudgetPlanRemovedDomainEvent' => BudgetPlanRemovedDomainEvent::class,
            'BudgetPlanWantRemovedDomainEvent' => BudgetPlanWantRemovedDomainEvent::class,
            'BudgetPlanNeedRemovedDomainEvent' => BudgetPlanNeedRemovedDomainEvent::class,
            'BudgetPlanSavingRemovedDomainEvent' => BudgetPlanSavingRemovedDomainEvent::class,
            'BudgetPlanIncomeRemovedDomainEvent' => BudgetPlanIncomeRemovedDomainEvent::class,
            'BudgetPlanGeneratedDomainEvent' => BudgetPlanGeneratedDomainEvent::class,
            'BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent' => BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class,
            'BudgetPlanIncomeAddedDomainEvent' => BudgetPlanIncomeAddedDomainEvent::class,
            'BudgetPlanNeedAddedDomainEvent' => BudgetPlanNeedAddedDomainEvent::class,
            'BudgetPlanSavingAddedDomainEvent' => BudgetPlanSavingAddedDomainEvent::class,
            'BudgetPlanWantAddedDomainEvent' => BudgetPlanWantAddedDomainEvent::class,
            'BudgetPlanIncomeAdjustedDomainEvent' => BudgetPlanIncomeAdjustedDomainEvent::class,
            'BudgetPlanNeedAdjustedDomainEvent' => BudgetPlanNeedAdjustedDomainEvent::class,
            'BudgetPlanSavingAdjustedDomainEvent' => BudgetPlanSavingAdjustedDomainEvent::class,
            'BudgetPlanWantAdjustedDomainEvent' => BudgetPlanWantAdjustedDomainEvent::class,
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
            return $this->getClassNameByEventPath($eventPath);
        }, $eventsPaths);
    }
}
