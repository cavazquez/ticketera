<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TimezoneOptions;
use Tests\TestCase;

class TimezoneOptionsTest extends TestCase
{
    public function test_groups_timezones_by_region(): void
    {
        $groups = TimezoneOptions::grouped();

        $this->assertNotEmpty($groups);
        $this->assertArrayHasKey('region', $groups[0]);
        $this->assertArrayHasKey('options', $groups[0]);
        $this->assertArrayHasKey('value', $groups[0]['options'][0]);
        $this->assertArrayHasKey('label', $groups[0]['options'][0]);
    }

    public function test_includes_a_known_timezone(): void
    {
        $values = collect(TimezoneOptions::grouped())
            ->flatMap(fn (array $group) => $group['options'])
            ->pluck('value');

        $this->assertTrue($values->contains('America/Argentina/Buenos_Aires'));
        $this->assertTrue($values->contains('UTC'));
    }
}
