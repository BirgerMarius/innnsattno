<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;

class ClockChangeReminderService
{
    private const AUTUMN_2026_REMINDER_END = '2026-10-25 13:00:00';

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
                if ($this->reminderEndsAt($change)->lessThanOrEqualTo($now)) {
                    return null;
                }

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
                $reminderEnd = $this->reminderEndsAt($change);
                $changes = array_merge($changes, [
                    [
                        'date' => $change->subDay()->startOfDay(),
                        'title' => 'Påminnelse om å stille klokken vises',
                        'description' => 'Påminnelse om å stille klokken '.$direction.' før overgang til '.$season.'.',
                        'type' => 'clock-change',
                        'preview' => ['kind' => 'clock', 'reminder' => $this->forDate($change->subDay()->startOfDay())],
                    ],
                    [
                        'date' => $change,
                        'title' => 'Teksten i klokkepåminnelsen endres',
                        'description' => 'Påminnelsen opplyser at klokken ble stilt '.$direction.' én time.',
                        'type' => 'clock-change',
                        'preview' => ['kind' => 'clock', 'reminder' => $this->forDate($change)],
                    ],
                    [
                        'date' => $reminderEnd,
                        'title' => 'Påminnelse om å stille klokken skjules',
                        'description' => $reminderEnd->isSameDay($change)
                            ? 'Påminnelsen fjernes automatisk kl. '.$reminderEnd->format('H:i').'.'
                            : 'Påminnelsen fjernes automatisk etter klokkeomstillingen.',
                        'type' => 'clock-change',
                        'preview' => ['kind' => 'clock', 'reminder' => $this->forDate($change), 'hidden' => true],
                    ],
                ]);
            }

            $previousOffset = $offset;
        }

        return array_values(array_filter($changes, fn (array $change) => $change['date']->greaterThan($now)));
    }

    public function dailyWordReplacementAt(CarbonImmutable $date): ?CarbonImmutable
    {
        $cutoff = CarbonImmutable::parse(self::AUTUMN_2026_REMINDER_END, 'Europe/Oslo');

        return $date->setTimezone('Europe/Oslo')->isSameDay($cutoff) ? $cutoff : null;
    }

    public function nextMessageChangeAt(?DateTimeInterface $date = null): ?CarbonImmutable
    {
        $now = $date
            ? CarbonImmutable::instance($date)->setTimezone('Europe/Oslo')
            : CarbonImmutable::now('Europe/Oslo');
        $cutoff = CarbonImmutable::parse(self::AUTUMN_2026_REMINDER_END, 'Europe/Oslo');

        return $now->lessThan($cutoff) ? $cutoff : null;
    }

    private function reminderEndsAt(CarbonImmutable $change): CarbonImmutable
    {
        return $this->dailyWordReplacementAt($change) ?? $change->addDay()->startOfDay();
    }
}
