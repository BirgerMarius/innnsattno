<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;

class ClockChangeReminderService
{
    public function forDate(?DateTimeInterface $date = null): ?array
    {
        $now = $date
            ? CarbonImmutable::instance($date)->setTimezone('Europe/Oslo')
            : CarbonImmutable::now('Europe/Oslo');
        $start = $now->startOfYear();
        $transitions = (new DateTimeZone('Europe/Oslo'))->getTransitions(
            $start->getTimestamp(),
            $start->addYear()->getTimestamp()
        );
        $previousOffset = $transitions[0]['offset'];

        // Read actual timezone transitions, rather than maintaining a list of dates.
        foreach (array_slice($transitions, 1) as $transition) {
            $offset = $transition['offset'];
            $change = CarbonImmutable::createFromTimestamp($transition['ts'], 'Europe/Oslo');
            $isChangeDay = $now->isSameDay($change);
            $isDayBefore = $now->isSameDay($change->subDay());

            if ($offset !== $previousOffset && ($isChangeDay || $isDayBefore)) {
                $forward = $offset > $previousOffset;
                $direction = $forward ? 'frem' : 'tilbake';
                $hasChanged = $now->getTimestamp() >= $transition['ts'];

                return [
                    'direction' => $direction,
                    'forward' => $forward,
                    'title' => 'Husk å stille klokken '.$direction,
                    'message' => ($hasChanged ? 'I natt ble klokken stilt ' : 'I natt stilles klokken ')
                        .$direction.' én time.',
                    'time' => gmdate('H:i', $transition['ts'] + $previousOffset)
                        .' → '.gmdate('H:i', $transition['ts'] + $offset),
                    'season' => $forward ? 'Til sommertid' : 'Til vintertid (normaltid)',
                ];
            }

            $previousOffset = $offset;
        }

        return null;
    }

    /**
     * @return array<int, array{date: CarbonImmutable, title: string, description: string, type: string}>
     */
    public function upcomingChanges(?DateTimeInterface $from = null): array
    {
        $now = $from
            ? CarbonImmutable::instance($from)->setTimezone('Europe/Oslo')
            : CarbonImmutable::now('Europe/Oslo');
        $transitions = (new DateTimeZone('Europe/Oslo'))->getTransitions(
            $now->startOfYear()->getTimestamp(),
            $now->addYear()->endOfYear()->getTimestamp()
        );
        $previousOffset = $transitions[0]['offset'];
        $changes = [];

        foreach (array_slice($transitions, 1) as $transition) {
            $change = CarbonImmutable::createFromTimestamp($transition['ts'], 'Europe/Oslo');
            $offset = $transition['offset'];

            if ($offset !== $previousOffset) {
                $direction = $offset > $previousOffset ? 'frem' : 'tilbake';
                $season = $direction === 'frem' ? 'sommertid' : 'normaltid';
                $changes = array_merge($changes, [
                    [
                        'date' => $change->subDay()->startOfDay(),
                        'title' => 'Påminnelse om å stille klokken vises',
                        'description' => 'Påminnelse om å stille klokken '.$direction.' før overgang til '.$season.'.',
                        'type' => 'clock-change',
                    ],
                    [
                        'date' => $change,
                        'title' => 'Teksten i klokkepåminnelsen endres',
                        'description' => 'Påminnelsen opplyser at klokken ble stilt '.$direction.' én time.',
                        'type' => 'clock-change',
                    ],
                    [
                        'date' => $change->addDay()->startOfDay(),
                        'title' => 'Påminnelse om å stille klokken skjules',
                        'description' => 'Påminnelsen fjernes automatisk etter klokkeomstillingen.',
                        'type' => 'clock-change',
                    ],
                ]);
            }

            $previousOffset = $offset;
        }

        return array_values(array_filter($changes, fn (array $change) => $change['date']->greaterThan($now)));
    }
}
