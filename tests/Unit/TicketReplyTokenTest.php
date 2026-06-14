<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TicketReplyToken;
use Tests\TestCase;

class TicketReplyTokenTest extends TestCase
{
    public function test_token_is_deterministic_and_eight_chars(): void
    {
        $a = TicketReplyToken::for('TKT-000001');
        $b = TicketReplyToken::for('TKT-000001');

        $this->assertSame($a, $b);
        $this->assertSame(8, strlen($a));
    }

    public function test_token_is_case_insensitive_on_ticket_number(): void
    {
        $this->assertSame(
            TicketReplyToken::for('TKT-000001'),
            TicketReplyToken::for('tkt-000001'),
        );
    }

    public function test_verify_accepts_valid_token_and_rejects_invalid(): void
    {
        $token = TicketReplyToken::for('TKT-000042');

        $this->assertTrue(TicketReplyToken::verify('TKT-000042', $token));
        $this->assertTrue(TicketReplyToken::verify('TKT-000042', strtoupper($token)));
        $this->assertFalse(TicketReplyToken::verify('TKT-000042', '00000000'));
        $this->assertFalse(TicketReplyToken::verify('TKT-000043', $token));
    }
}
