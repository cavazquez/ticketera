<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Support\EnumOptions;
use PHPUnit\Framework\TestCase;

class EnumOptionsTest extends TestCase
{
    public function test_maps_every_case_to_value_and_label(): void
    {
        $options = EnumOptions::from(UserRole::class);

        $this->assertCount(count(UserRole::cases()), $options);
        $this->assertSame('cliente', $options[0]['value']);
        $this->assertArrayHasKey('label', $options[0]);
        $this->assertArrayNotHasKey('color', $options[0]);
    }

    public function test_includes_color_when_requested_and_available(): void
    {
        $options = EnumOptions::from(TicketStatus::class, true);

        $this->assertArrayHasKey('color', $options[0]);
        $this->assertNotSame('', $options[0]['color']);
    }
}
