<?php

namespace Tests\Unit;

use App\Services\Schibsted\JsonStructureInspector;
use Tests\TestCase;

class JsonStructureInspectorTest extends TestCase
{
    /** @test */
    public function it_summarizes_generic_json_without_sensitive_values(): void
    {
        $summary = (new JsonStructureInspector())->inspect([
            'events' => [
                [
                    'id' => 55,
                    'participantIds' => [1, 2],
                    'authorEmail' => 'person@example.com',
                    'tournament' => ['id' => 19, 'seasonId' => 7767],
                ],
            ],
            'standings' => [],
        ]);

        $this->assertSame('object', $summary['top_level_type']);
        $this->assertContains('events', $summary['top_level_keys']);
        $this->assertSame(1, $summary['list_counts']['events']['total']);
        $this->assertTrue($summary['signals']['events']);
        $this->assertTrue($summary['signals']['standings']);
        $this->assertContains('events.*.id', $summary['id_fields']['examples']);
        $this->assertNotContains('authorEmail', $summary['first_item_fields']['events']);
    }

    /** @test */
    public function it_limits_repeated_list_examples_and_reports_hidden_counts(): void
    {
        $events = [];

        for ($i = 1; $i <= 12; $i++) {
            $events[] = [
                'id' => $i,
                'participantIds' => [$i, $i + 100],
            ];
        }

        $summary = (new JsonStructureInspector())->inspect(['events' => $events]);

        $participantIds = $summary['list_counts']['events.*.participantIds'];

        $this->assertSame(12, $participantIds['total']);
        $this->assertSame(5, $participantIds['shown']);
        $this->assertSame(7, $participantIds['hidden']);
        $this->assertSame('events.0.participantIds', $participantIds['examples'][0]['path']);
    }

    /** @test */
    public function it_limits_id_field_examples_and_keeps_total_count(): void
    {
        $events = [];

        for ($i = 1; $i <= 15; $i++) {
            $events[] = [
                'id' => $i,
                'tournament' => ['seasonId' => 7767 + $i],
            ];
        }

        $summary = (new JsonStructureInspector())->inspect(['events' => $events]);

        $this->assertSame(30, $summary['id_fields']['total']);
        $this->assertSame(2, $summary['id_fields']['shown']);
        $this->assertSame(28, $summary['id_fields']['hidden']);
        $this->assertSame(['events.*.id', 'events.*.tournament.seasonId'], $summary['id_fields']['examples']);
    }
}
